<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientVisitDiagnosisRequest;
use App\Http\Requests\UpdatePatientVisitDiagnosisRequest;
use App\Http\Resources\PatientVisitDiagnosisResource;
use App\Models\PatientVisit;
use App\Models\PatientVisitDiagnosis;
use App\Services\ClinicalMasterService;
use Illuminate\Http\Request;

class PatientVisitDiagnosisController extends Controller
{
    public function __construct(protected ClinicalMasterService $clinicalService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PatientVisitDiagnosis::class);

        $query = PatientVisitDiagnosis::with(['diagnosisMaster', 'createdBy'])->latest();

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        return PatientVisitDiagnosisResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StorePatientVisitDiagnosisRequest $request)
    {
        $visit = PatientVisit::findOrFail($request->patient_visit_id);
        $this->authorize('view', $visit);

        $master = $request->diagnosis_master_id
            ? \App\Models\DiagnosisMaster::findOrFail($request->diagnosis_master_id)
            : $this->clinicalService->findOrCreateDiagnosis($request->diagnosis_text, $request->user());

        if (! $request->boolean('force')) {
            $duplicate = PatientVisitDiagnosis::query()
                ->where('patient_visit_id', $visit->id)
                ->where('diagnosis_master_id', $master->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'This diagnosis is already added to this visit.',
                    'code'    => 'duplicate_visit_diagnosis',
                ], 422);
            }
        }

        $record = PatientVisitDiagnosis::create([
            'patient_id'          => $request->patient_id,
            'patient_visit_id'    => $request->patient_visit_id,
            'diagnosis_master_id' => $master->id,
            'diagnosis_text'      => $request->diagnosis_text,
            'created_by'          => $request->user()->id,
            'updated_by'          => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Diagnosis added successfully.',
            'data'    => new PatientVisitDiagnosisResource($record->load(['diagnosisMaster', 'createdBy'])),
        ], 201);
    }

    public function show(PatientVisitDiagnosis $patientVisitDiagnosis)
    {
        $this->authorize('view', $patientVisitDiagnosis);

        return new PatientVisitDiagnosisResource(
            $patientVisitDiagnosis->load(['diagnosisMaster', 'createdBy', 'visit'])
        );
    }

    public function update(UpdatePatientVisitDiagnosisRequest $request, PatientVisitDiagnosis $patientVisitDiagnosis)
    {
        $master = $request->diagnosis_master_id
            ? \App\Models\DiagnosisMaster::findOrFail($request->diagnosis_master_id)
            : $this->clinicalService->findOrCreateDiagnosis($request->diagnosis_text, $request->user());

        $patientVisitDiagnosis->update([
            'diagnosis_master_id' => $master->id,
            'diagnosis_text'      => $request->diagnosis_text,
            'updated_by'          => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Diagnosis updated successfully.',
            'data'    => new PatientVisitDiagnosisResource($patientVisitDiagnosis->fresh(['diagnosisMaster', 'createdBy'])),
        ]);
    }

    public function destroy(PatientVisitDiagnosis $patientVisitDiagnosis)
    {
        $this->authorize('delete', $patientVisitDiagnosis);

        $patientVisitDiagnosis->delete();

        return response()->json(['message' => 'Diagnosis deleted successfully.']);
    }

    public function byVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('view', $visit);

        $diagnoses = $visit->diagnoses()
            ->with(['diagnosisMaster', 'createdBy'])
            ->latest()
            ->get();

        return PatientVisitDiagnosisResource::collection($diagnoses);
    }
}
