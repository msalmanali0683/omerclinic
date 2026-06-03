<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicalScan;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Services\ClinicalScanHistoryService;
use Illuminate\Http\Request;

class ClinicalScanHistoryController extends Controller
{
    public function __construct(protected ClinicalScanHistoryService $historyService) {}

    public function byPatient(Request $request, Patient $patient)
    {
        $this->authorize('viewHistory', ClinicalScan::class);
        $this->ensureCanAccessPatientHistory($request, $patient);

        $history = $this->historyService->forPatient(
            $patient,
            $request->integer('current_visit_id') ?: null,
            $request->integer('exclude_visit_id') ?: null,
            $request->integer('limit', 50)
        );

        return response()->json($history);
    }

    protected function ensureCanAccessPatientHistory(Request $request, Patient $patient): void
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super-admin', 'hospital-admin', 'scan-operator', 'nurse', 'receptionist'])) {
            return;
        }

        if ($user->hasRole('doctor')) {
            $hasVisit = PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $user->id)
                ->exists();

            abort_unless($hasVisit, 403, 'You are not authorized to view clinical scan history for this patient.');
        }
    }
}
