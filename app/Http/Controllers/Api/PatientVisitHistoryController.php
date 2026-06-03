<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitComplaintResource;
use App\Http\Resources\PatientVisitDiagnosisResource;
use App\Http\Resources\PatientVisitResource;
use App\Http\Resources\PatientVitalResource;
use App\Http\Resources\PrescriptionMedicineResource;
use App\Http\Resources\PrescriptionResource;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Policies\PatientVisitPolicy;
use App\Support\PrescriptionVisitMeta;
use Illuminate\Http\Request;

class PatientVisitHistoryController extends Controller
{
    public function indexByPatient(Request $request, Patient $patient)
    {
        $this->authorizePatientView($request, $patient);
        abort_unless(app(PatientVisitPolicy::class)->viewHistory($request->user(), $patient), 403);

        $query = PatientVisit::query()
            ->with('doctor')
            ->where('patient_id', $patient->id)
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->orderByDesc('id');

        if (! $request->user()->can('view full patient visit history') && $request->user()->hasRole('doctor')) {
            $query->where('doctor_id', $request->user()->id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('visit_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('visit_date', '<=', $request->to_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $visits = $query->paginate($request->get('per_page', 20));

        $isLimited = $this->isLimitedHistoryUser($request);

        $items = collect($visits->items())->map(function (PatientVisit $visit) use ($isLimited, $request) {
            $visit->loadMissing('prescription');
            $item = array_merge(
                (new PatientVisitResource($visit))->resolve(),
                PrescriptionVisitMeta::forVisit($visit, $request->user())
            );

            if ($isLimited) {
                unset($item['reason_for_visit'], $item['notes']);
            }

            return $item;
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page'    => $visits->lastPage(),
                'per_page'     => $visits->perPage(),
                'total'        => $visits->total(),
            ],
            'patient' => $this->patientPayload($request, $patient),
        ]);
    }

    public function showVisitDetails(Request $request, Patient $patient, PatientVisit $visit)
    {
        $this->authorizePatientView($request, $patient);

        if ((int) $visit->patient_id !== (int) $patient->id) {
            abort(404, 'Visit not found for this patient.');
        }

        abort_unless(app(PatientVisitPolicy::class)->viewDetails($request->user(), $visit), 403);

        $visit->load(['doctor', 'queuedBy', 'createdBy', 'updatedBy', 'prescription']);

        $response = [
            'patient' => $this->patientPayload($request, $patient),
            'visit'   => array_merge(
                (new PatientVisitResource($visit))->resolve(),
                PrescriptionVisitMeta::forVisit($visit, $request->user())
            ),
        ];

        if ($this->isLimitedDetailsOnly($request)) {
            return response()->json($response);
        }

        $user = $request->user();

        if ($user->can('view patient vitals')) {
            $visit->load('latestVitals.recordedBy');
            $response['vitals'] = $visit->latestVitals
                ? new PatientVitalResource($visit->latestVitals)
                : null;
        }

        if ($user->can('view visit complaints')) {
            $response['complaints'] = PatientVisitComplaintResource::collection(
                $visit->complaints()->with(['complaintMaster', 'createdBy'])->latest()->get()
            );
        }

        if ($user->can('view visit diagnosis')) {
            $response['diagnoses'] = PatientVisitDiagnosisResource::collection(
                $visit->diagnoses()->with(['diagnosisMaster', 'createdBy'])->latest()->get()
            );
        }

        if ($user->can('view prescriptions')) {
            $prescription = $visit->prescription()
                ->with(['medicineItems.doseTime', 'medicineItems.doseFromMeal', 'doctor'])
                ->first();

            $response['prescription'] = $prescription
                ? new PrescriptionResource($prescription)
                : null;

            if ($prescription && $user->can('view medicines')) {
                $response['prescription_medicines'] = PrescriptionMedicineResource::collection(
                    $prescription->medicineItems
                );
            }
        }

        if ($user->can('view clinical scans') || $user->can('view patient clinical scan history')) {
            $response['clinical_scans'] = \App\Http\Resources\ClinicalScanResource::collection(
                $visit->clinicalScans()
                    ->with(['values', 'scanOperator'])
                    ->latest('scan_date')
                    ->latest('id')
                    ->get()
            );
        }

        if ($user->can('view laboratory results') || $user->can('view patient laboratory history')) {
            $response['laboratory_results'] = \App\Http\Resources\LaboratoryResultResource::collection(
                $visit->laboratoryResults()
                    ->with(['values', 'labOperator'])
                    ->latest('result_date')
                    ->latest('id')
                    ->get()
            );
        }

        return response()->json($response);
    }

    protected function authorizePatientView(Request $request, Patient $patient): void
    {
        $this->authorize('view', $patient);
    }

    protected function isLimitedHistoryUser(Request $request): bool
    {
        $user = $request->user();

        return $user->can('view limited patient visit history')
            && ! $user->can('view full patient visit history')
            && ! $user->can('view patient visits');
    }

    protected function isLimitedDetailsOnly(Request $request): bool
    {
        $user = $request->user();

        if ($user->can('view patient visit details') || $user->can('view full patient visit history')) {
            return false;
        }

        return $user->can('view limited patient visit history');
    }

    protected function patientPayload(Request $request, Patient $patient): array
    {
        $data = (new PatientResource($patient))->resolve();

        if ($request->user()->can('view patients')) {
            return $data;
        }

        return [
            'id'                  => $data['id'],
            'mr_number'           => $data['mr_number'],
            'patient_name'        => $data['patient_name'],
            'patient_gender'      => $data['patient_gender'] ?? null,
            'patient_age'         => $data['patient_age'] ?? null,
            'patient_age_unit'    => $data['patient_age_unit'] ?? null,
            'patient_age_display' => $data['patient_age_display'] ?? null,
            'patient_cell'        => $data['patient_cell'] ?? null,
        ];
    }
}
