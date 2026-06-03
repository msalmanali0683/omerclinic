<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaboratoryResultResource;
use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Services\LaboratoryHistoryService;
use Illuminate\Http\Request;

class LaboratoryHistoryController extends Controller
{
    public function __construct(protected LaboratoryHistoryService $historyService) {}

    public function byPatient(Request $request, Patient $patient)
    {
        $this->authorize('viewHistory', LaboratoryResult::class);
        $this->ensureCanAccessPatientHistory($request, $patient);

        $history = $this->historyService->forPatient(
            $patient,
            $request->integer('current_visit_id') ?: null,
            $request->integer('exclude_visit_id') ?: null,
            $request->integer('limit', 50)
        );

        return response()->json($history);
    }

    public function byVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('viewAny', LaboratoryResult::class);

        $results = LaboratoryResult::with(['values', 'labOperator'])
            ->where('patient_visit_id', $visit->id)
            ->where('status', '!=', LaboratoryResult::STATUS_CANCELLED)
            ->latest('result_date')
            ->latest('id')
            ->get();

        return LaboratoryResultResource::collection($results);
    }

    protected function ensureCanAccessPatientHistory(Request $request, Patient $patient): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super-admin', 'hospital-admin', 'lab-technician', 'lab-operator', 'nurse'])) {
            return;
        }

        if ($user->hasRole('doctor')) {
            $hasVisit = PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $user->id)
                ->exists();

            abort_unless($hasVisit, 403, 'You are not authorized to view laboratory history for this patient.');
        }
    }
}
