<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientVisitComplaintRequest;
use App\Http\Requests\UpdatePatientVisitComplaintRequest;
use App\Http\Resources\PatientVisitComplaintResource;
use App\Models\PatientVisit;
use App\Models\PatientVisitComplaint;
use App\Services\ClinicalMasterService;
use Illuminate\Http\Request;

class PatientVisitComplaintController extends Controller
{
    public function __construct(protected ClinicalMasterService $clinicalService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', PatientVisitComplaint::class);

        $query = PatientVisitComplaint::with(['complaintMaster', 'createdBy'])->latest();

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        return PatientVisitComplaintResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StorePatientVisitComplaintRequest $request)
    {
        $visit = PatientVisit::findOrFail($request->patient_visit_id);
        $this->authorize('view', $visit);

        $master = $request->complaint_master_id
            ? \App\Models\ComplaintMaster::findOrFail($request->complaint_master_id)
            : $this->clinicalService->findOrCreateComplaint($request->complaint_text, $request->user());

        if (! $request->boolean('force')) {
            $duplicate = PatientVisitComplaint::query()
                ->where('patient_visit_id', $visit->id)
                ->where('complaint_master_id', $master->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'This complaint is already added to this visit.',
                    'code'    => 'duplicate_visit_complaint',
                ], 422);
            }
        }

        $record = PatientVisitComplaint::create([
            'patient_id'          => $request->patient_id,
            'patient_visit_id'    => $request->patient_visit_id,
            'complaint_master_id' => $master->id,
            'complaint_text'      => $request->complaint_text,
            'created_by'          => $request->user()->id,
            'updated_by'          => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Complaint added successfully.',
            'data'    => new PatientVisitComplaintResource($record->load(['complaintMaster', 'createdBy'])),
        ], 201);
    }

    public function show(PatientVisitComplaint $patientVisitComplaint)
    {
        $this->authorize('view', $patientVisitComplaint);

        return new PatientVisitComplaintResource(
            $patientVisitComplaint->load(['complaintMaster', 'createdBy', 'visit'])
        );
    }

    public function update(UpdatePatientVisitComplaintRequest $request, PatientVisitComplaint $patientVisitComplaint)
    {
        $master = $request->complaint_master_id
            ? \App\Models\ComplaintMaster::findOrFail($request->complaint_master_id)
            : $this->clinicalService->findOrCreateComplaint($request->complaint_text, $request->user());

        $patientVisitComplaint->update([
            'complaint_master_id' => $master->id,
            'complaint_text'      => $request->complaint_text,
            'updated_by'          => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Complaint updated successfully.',
            'data'    => new PatientVisitComplaintResource($patientVisitComplaint->fresh(['complaintMaster', 'createdBy'])),
        ]);
    }

    public function destroy(PatientVisitComplaint $patientVisitComplaint)
    {
        $this->authorize('delete', $patientVisitComplaint);

        $patientVisitComplaint->delete();

        return response()->json(['message' => 'Complaint deleted successfully.']);
    }

    public function byVisit(Request $request, PatientVisit $visit)
    {
        $this->authorize('view', $visit);

        $complaints = $visit->complaints()
            ->with(['complaintMaster', 'createdBy'])
            ->latest()
            ->get();

        return PatientVisitComplaintResource::collection($complaints);
    }
}
