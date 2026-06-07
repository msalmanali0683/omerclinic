<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddPatientToQueueRequest;
use App\Http\Requests\AssignQueueDoctorRequest;
use App\Http\Resources\PatientVisitComplaintResource;
use App\Http\Resources\PatientVisitDiagnosisResource;
use App\Http\Resources\PatientVisitResource;
use App\Http\Resources\PatientVitalResource;
use App\Models\ClinicalScan;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PatientVisitComplaint;
use App\Models\PatientVisitDiagnosis;
use App\Models\PatientVital;
use App\Services\ClinicalScanHistoryService;
use App\Services\PatientQueueService;
use App\Services\PatientVisitTokenService;
use App\Support\PatientVisitTokenResponse;
use App\Support\PrescriptionVisitMeta;
use Illuminate\Http\Request;

class PatientQueueController extends Controller
{
    public function __construct(
        protected PatientQueueService $queueService,
        protected PatientVisitTokenService $tokenService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PatientVisit::class);

        $user = $request->user();
        $statuses = $request->get('status', PatientVisit::ACTIVE_STATUSES);
        if (is_string($statuses)) {
            $statuses = array_filter(explode(',', $statuses));
        }

        $query = PatientVisit::with(['patient', 'doctor', 'queuedBy'])
            ->whereIn('status', $statuses)
            ->latest();

        if ($user->hasRole('doctor') && ! $user->can('view all patient queue')) {
            $query->where('doctor_id', $user->id);
        } elseif ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        } elseif ($request->boolean('assigned_to_me')) {
            $query->where('doctor_id', $user->id);
        } elseif ($request->boolean('doctor_scope') && $user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }

        if ($request->filled('visit_date')) {
            $query->whereDate('visit_date', $request->visit_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('mr_number', 'like', "%{$search}%")
                    ->orWhere('patient_cell', 'like', "%{$search}%");
            });
        }

        $visits = $query->paginate($request->get('per_page', 15));

        return PatientVisitResource::collection($visits);
    }

    public function addToQueue(AddPatientToQueueRequest $request, Patient $patient)
    {
        $this->authorize('addToQueue', PatientVisit::class);

        $data = $request->validated();
        $user = $request->user();

        if ($user->hasRole('doctor')) {
            if (! $user->can('assign doctor to queue')) {
                $data['doctor_id'] = $user->id;
            } elseif (empty($data['doctor_id'])) {
                $data['doctor_id'] = $user->id;
            }
        }

        $result = $this->queueService->addToQueue($patient, $user, $data);

        $response = [
            'message' => $result['message'],
            'patient' => new \App\Http\Resources\PatientResource($patient),
            'visit'   => new PatientVisitResource($result['visit']),
            'created' => $result['created'],
        ];

        $response = PatientVisitTokenResponse::appendAutoToken(
            $response,
            $result['visit'],
            $user,
            $this->tokenService,
            'Patient visit added and token generated successfully.',
            $result['created'],
        );

        return response()->json($response, $result['created'] ? 201 : 200);
    }

    public function show(Request $request, PatientVisit $visit)
    {
        $this->authorize('view', $visit);

        $visit->load(['patient', 'doctor', 'queuedBy', 'latestVitals.recordedBy', 'complaints.complaintMaster', 'complaints.createdBy', 'diagnoses.diagnosisMaster', 'diagnoses.createdBy', 'prescription']);

        $previousVisits = $visit->patient->visits()
            ->where('id', '!=', $visit->id)
            ->latest()
            ->limit(10)
            ->get();

        $response = [
            'visit'           => array_merge(
                (new PatientVisitResource($visit))->resolve(),
                PrescriptionVisitMeta::forVisit($visit, $request->user())
            ),
            'previous_visits' => PatientVisitResource::collection($previousVisits),
            'latest_vitals'   => $visit->latestVitals
                ? new PatientVitalResource($visit->latestVitals)
                : null,
            'visit_complaints' => PatientVisitComplaintResource::collection($visit->complaints),
            'visit_diagnoses'  => PatientVisitDiagnosisResource::collection($visit->diagnoses),
        ];

        if ($request->user()->can('viewHistory', PatientVital::class)) {
            $response['vitals_history'] = PatientVitalResource::collection(
                PatientVital::query()
                    ->where('patient_id', $visit->patient_id)
                    ->where('patient_visit_id', '!=', $visit->id)
                    ->with(['visit', 'recordedBy', 'patient'])
                    ->latest('recorded_at')
                    ->limit(20)
                    ->get()
            );
        }

        if ($request->user()->can('viewHistory', ClinicalScan::class)) {
            $response['clinical_scan_history'] = app(ClinicalScanHistoryService::class)
                ->forPatient($visit->patient, $visit->id);
        }

        if ($request->user()->can('viewHistory', \App\Models\LaboratoryResult::class)) {
            $response['laboratory_history'] = app(\App\Services\LaboratoryHistoryService::class)
                ->forPatient($visit->patient, $visit->id);
        }

        if ($request->user()->can('viewHistory', PatientVisitComplaint::class)) {
            $response['complaints_history'] = PatientVisitComplaintResource::collection(
                PatientVisitComplaint::query()
                    ->where('patient_id', $visit->patient_id)
                    ->where('patient_visit_id', '!=', $visit->id)
                    ->with(['complaintMaster', 'createdBy', 'visit'])
                    ->latest()
                    ->limit(20)
                    ->get()
            );
        }

        if ($request->user()->can('viewHistory', PatientVisitDiagnosis::class)) {
            $response['diagnosis_history'] = PatientVisitDiagnosisResource::collection(
                PatientVisitDiagnosis::query()
                    ->where('patient_id', $visit->patient_id)
                    ->where('patient_visit_id', '!=', $visit->id)
                    ->with(['diagnosisMaster', 'createdBy', 'visit'])
                    ->latest()
                    ->limit(20)
                    ->get()
            );
        }

        return response()->json($response);
    }

    public function assignDoctor(AssignQueueDoctorRequest $request, PatientVisit $visit)
    {
        $this->authorize('assignDoctor', $visit);

        $visit->update([
            'doctor_id'  => $request->doctor_id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Doctor assigned successfully.',
            'visit'   => new PatientVisitResource($visit->fresh(['patient', 'doctor', 'queuedBy'])),
        ]);
    }

    public function startConsultation(Request $request, PatientVisit $visit)
    {
        $this->authorize('startConsultation', $visit);

        if ($visit->doctor_id === null && $request->user()->hasRole('doctor')) {
            $visit->doctor_id = $request->user()->id;
        }

        $visit->update([
            'status'     => PatientVisit::STATUS_IN_CONSULTATION,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Consultation started.',
            'visit'   => new PatientVisitResource($visit->fresh(['patient', 'doctor', 'queuedBy'])),
        ]);
    }

    public function markPrescribed(Request $request, PatientVisit $visit)
    {
        $this->authorize('markPrescribed', $visit);

        $visit->update([
            'status'     => PatientVisit::STATUS_PRESCRIBED,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Visit marked as prescribed.',
            'visit'   => new PatientVisitResource($visit->fresh(['patient', 'doctor', 'queuedBy'])),
        ]);
    }

    public function returnToPendingPrescription(Request $request, PatientVisit $visit)
    {
        $this->authorize('returnToPendingPrescription', $visit);

        $visit = $this->queueService->returnToPendingPrescription($visit, $request->user());

        return response()->json([
            'message' => 'Visit returned to pending prescription and doctor queue.',
            'visit'   => new PatientVisitResource($visit),
        ]);
    }

    public function cancel(Request $request, PatientVisit $visit)
    {
        $this->authorize('cancel', $visit);

        $visit->update([
            'status'     => PatientVisit::STATUS_CANCELLED,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Queue entry cancelled.',
            'visit'   => new PatientVisitResource($visit->fresh(['patient', 'doctor', 'queuedBy'])),
        ]);
    }

    public function cancelStale(Request $request)
    {
        $this->authorize('cancelStale', PatientVisit::class);

        $count = $this->queueService->countStaleQueueVisits();

        if ($count === 0) {
            return response()->json([
                'message' => 'No old queue entries to cancel.',
                'cancelled_count' => 0,
            ]);
        }

        $cancelled = $this->queueService->cancelStaleQueueVisits($request->user());

        return response()->json([
            'message' => $cancelled === 1
                ? 'Cancelled 1 old queue entry.'
                : "Cancelled {$cancelled} old queue entries.",
            'cancelled_count' => $cancelled,
        ]);
    }
}
