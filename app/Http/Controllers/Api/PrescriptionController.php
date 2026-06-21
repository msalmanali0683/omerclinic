<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Requests\UpdatePrescriptionRequest;
use App\Http\Resources\PatientVisitComplaintResource;
use App\Http\Resources\PatientVisitDiagnosisResource;
use App\Http\Resources\PatientVisitResource;
use App\Http\Resources\PatientVitalResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\ClinicalScan;
use App\Models\Medicine;
use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Services\ClinicalScanHistoryService;
use App\Services\PrescriptionPrintDataService;
use App\Services\PrescriptionService;
use App\Support\PrescriptionVisitMeta;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(
        protected PrescriptionService $prescriptionService,
        protected PrescriptionPrintDataService $printDataService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Prescription::class);

        $query = Prescription::with(['patient', 'doctor', 'visit', 'medicineItems'])
            ->latest();

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        return PrescriptionResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StorePrescriptionRequest $request)
    {
        $visit = PatientVisit::findOrFail($request->patient_visit_id);
        $this->authorize('markPrescribed', $visit);

        $existing = Prescription::query()
            ->where('patient_visit_id', $visit->id)
            ->first();

        if ($existing) {
            $this->authorize('view', $existing);

            return response()->json([
                'message'         => 'Prescription already exists for this visit. Please update existing prescription.',
                'prescription_id' => $existing->id,
                'already_exists'  => true,
                'data'            => new PrescriptionResource(
                    $existing->load(['patient', 'doctor', 'visit', 'medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine'])
                ),
            ], 409);
        }

        try {
            $prescription = $this->prescriptionService->create($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'duplicate_prescription:')) {
                $prescriptionId = (int) str_replace('duplicate_prescription:', '', $e->getMessage());
                $existing = Prescription::findOrFail($prescriptionId);
                $this->authorize('view', $existing);

                return response()->json([
                    'message'         => 'Prescription already exists for this visit. Please update existing prescription.',
                    'prescription_id' => $existing->id,
                    'already_exists'  => true,
                    'data'            => new PrescriptionResource(
                        $existing->load(['patient', 'doctor', 'visit', 'medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine'])
                    ),
                ], 409);
            }

            throw $e;
        }

        return response()->json([
            'message'    => 'Prescription saved successfully.',
            'data'       => new PrescriptionResource($prescription),
            'print_data' => $this->printDataService->getPrintData($prescription),
            'can_print'  => $request->user()->can('print', $prescription),
        ], 201);
    }

    public function printData(Prescription $prescription)
    {
        try {
            $this->authorize('print', $prescription);
        } catch (AuthorizationException) {
            return response()->json([
                'message' => 'You are not authorized to print this prescription.',
            ], 403);
        }

        return response()->json([
            'print_data' => $this->printDataService->getPrintData(
                $prescription->load(['medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine', 'patient', 'doctor', 'visit'])
            ),
        ]);
    }

    public function show(Prescription $prescription)
    {
        $this->authorize('view', $prescription);

        return new PrescriptionResource(
            $prescription->load(['patient', 'doctor', 'visit', 'medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine'])
        );
    }

    public function update(UpdatePrescriptionRequest $request, Prescription $prescription)
    {
        $prescription = $this->prescriptionService->update($prescription, $request->validated(), $request->user());

        return response()->json([
            'message'    => 'Prescription updated successfully.',
            'data'       => new PrescriptionResource($prescription),
            'prescription' => new PrescriptionResource($prescription),
            'print_data' => $this->printDataService->getPrintData($prescription),
            'can_print'  => $request->user()->can('print', $prescription),
        ]);
    }

    public function showByVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('view', $visit);

        $prescription = $visit->prescription()
            ->with(['medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine', 'patient', 'doctor', 'visit'])
            ->firstOrFail();

        $this->authorize('view', $prescription);

        $visit->load([
            'patient',
            'doctor',
            'latestVitals.recordedBy',
            'complaints.complaintMaster',
            'complaints.createdBy',
            'diagnoses.diagnosisMaster',
            'diagnoses.createdBy',
        ]);

        return response()->json([
            'prescription' => new PrescriptionResource($prescription),
            'visit'        => array_merge(
                (new PatientVisitResource($visit))->resolve(),
                PrescriptionVisitMeta::forVisit($visit, $request->user())
            ),
            'patient'            => $visit->patient,
            'latest_vitals'      => $visit->latestVitals
                ? new PatientVitalResource($visit->latestVitals)
                : null,
            'visit_complaints'   => PatientVisitComplaintResource::collection($visit->complaints),
            'visit_diagnoses'    => PatientVisitDiagnosisResource::collection($visit->diagnoses),
            'dose_time_options'  => $this->doseTimeOptionsPayload(),
            'dose_from_meal_options' => $this->doseFromMealOptionsPayload(),
            ...PrescriptionVisitMeta::forVisit($visit, $request->user()),
        ]);
    }

    public function updateByVisit(UpdatePrescriptionRequest $request, PatientVisit $visit)
    {
        $prescription = $visit->prescription()->firstOrFail();

        return $this->update($request, $prescription);
    }

    public function destroy(Prescription $prescription)
    {
        $this->authorize('delete', $prescription);

        $prescription->medicineItems()->each(fn ($item) => $item->delete());
        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully.']);
    }

    public function prescriptionCreateData(Request $request, PatientVisit $visit)
    {
        $this->authorize('view', $visit);
        $this->authorize('create', Prescription::class);

        $visit->load([
            'patient',
            'doctor',
            'latestVitals.recordedBy',
            'complaints.complaintMaster',
            'complaints.createdBy',
            'diagnoses.diagnosisMaster',
            'diagnoses.createdBy',
        ]);

        $payload = [
            'visit'              => new PatientVisitResource($visit),
            'patient'            => $visit->patient,
            'latest_vitals'      => $visit->latestVitals
                ? new PatientVitalResource($visit->latestVitals)
                : null,
            'visit_complaints'   => PatientVisitComplaintResource::collection($visit->complaints),
            'visit_diagnoses'    => PatientVisitDiagnosisResource::collection($visit->diagnoses),
            'medicine_options'   => $this->medicineOptionsPayload($request),
            'dose_time_options'  => $this->doseTimeOptionsPayload(),
            'dose_from_meal_options' => $this->doseFromMealOptionsPayload(),
        ];

        if ($request->user()->can('viewHistory', ClinicalScan::class)) {
            $payload['clinical_scan_history'] = app(ClinicalScanHistoryService::class)
                ->forPatient($visit->patient, $visit->id);
        }

        if ($request->user()->can('viewHistory', \App\Models\LaboratoryResult::class)) {
            $payload['laboratory_history'] = app(\App\Services\LaboratoryHistoryService::class)
                ->forPatient($visit->patient, $visit->id);
        }

        return response()->json($payload);
    }

    protected function medicineOptionsPayload(Request $request): array
    {
        $this->authorize('viewAny', Medicine::class);

        $query = Medicine::query()->orderBy('mdcn_name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('mdcn_name', 'like', $search.'%');
        }

        return $query
            ->limit($request->get('limit', 150))
            ->get([
                'id',
                'mdcn_type',
                'mdcn_name',
                'mdcn_size',
                'mdcn_time_id',
                'mdcn_dose_from_meal_id',
            ])
            ->map(fn (Medicine $medicine) => [
                'id'                     => $medicine->id,
                'label'                  => $medicine->displayLabel(),
                'value'                  => $medicine->id,
                'mdcn_type'              => $medicine->mdcn_type,
                'mdcn_name'              => $medicine->mdcn_name,
                'mdcn_size'              => $medicine->mdcn_size,
                'mdcn_time_id'           => $medicine->mdcn_time_id,
                'mdcn_dose_from_meal_id' => $medicine->mdcn_dose_from_meal_id,
                'dose_time'              => null,
                'dose_from_meal'         => null,
            ])->all();
    }

    protected function doseTimeOptionsPayload(): array
    {
        return MedicineDoseTime::query()->orderBy('dose_time')->get()->map(fn ($item) => [
            'id'    => $item->id,
            'label' => $item->dose_time,
            'value' => $item->id,
        ])->all();
    }

    protected function doseFromMealOptionsPayload(): array
    {
        return MedicineDoseFromMeal::query()->orderBy('dose_from_meal')->get()->map(fn ($item) => [
            'id'    => $item->id,
            'label' => $item->dose_from_meal,
            'value' => $item->id,
        ])->all();
    }
}
