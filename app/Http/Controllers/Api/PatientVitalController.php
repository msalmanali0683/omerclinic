<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientVitalRequest;
use App\Http\Requests\UpdatePatientVitalRequest;
use App\Http\Resources\PatientVitalResource;
use App\Models\Patient;
use App\Models\PatientVital;
use App\Models\PatientVisit;
use Illuminate\Http\Request;

class PatientVitalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', PatientVital::class);

        $query = PatientVital::with(['patient', 'visit', 'recordedBy'])->latest('recorded_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('patient_visit_id')) {
            $query->where('patient_visit_id', $request->patient_visit_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('recorded_at', $request->date);
        }

        return PatientVitalResource::collection(
            $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(StorePatientVitalRequest $request)
    {
        $vital = PatientVital::create([
            ...$request->validated(),
            'recorded_by' => $request->user()->id,
            'recorded_at' => $request->recorded_at ?? now(),
        ]);

        return response()->json([
            'message' => 'Vitals recorded successfully.',
            'vital'   => new PatientVitalResource($vital->load(['patient', 'visit', 'recordedBy'])),
        ], 201);
    }

    public function show(PatientVital $vital)
    {
        $this->authorize('view', $vital);

        return new PatientVitalResource($vital->load(['patient', 'visit', 'recordedBy']));
    }

    public function update(UpdatePatientVitalRequest $request, PatientVital $vital)
    {
        $vital->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Vitals updated successfully.',
            'vital'   => new PatientVitalResource($vital->fresh(['patient', 'visit', 'recordedBy'])),
        ]);
    }

    public function destroy(PatientVital $vital)
    {
        $this->authorize('delete', $vital);

        $vital->delete();

        return response()->json(['message' => 'Vitals deleted successfully.']);
    }

    public function latestByVisit(PatientVisit $visit)
    {
        $this->authorize('view', $visit);

        $latest = PatientVital::query()
            ->where('patient_visit_id', $visit->id)
            ->with(['recordedBy', 'visit'])
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'vital' => $latest ? new PatientVitalResource($latest) : null,
        ]);
    }

    public function historyByPatient(Request $request, Patient $patient)
    {
        $this->authorize('viewHistory', PatientVital::class);

        $vitals = PatientVital::query()
            ->where('patient_id', $patient->id)
            ->with(['visit', 'recordedBy', 'patient'])
            ->latest('recorded_at')
            ->paginate($request->get('per_page', 20));

        return PatientVitalResource::collection($vitals);
    }
}
