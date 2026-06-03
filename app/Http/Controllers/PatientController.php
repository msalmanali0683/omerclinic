<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PatientController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Patient::class);

        // Fetch patients based on user role (typically handled via scope or simple logic)
        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin')) {
            $patients = Patient::all();
        } elseif ($user->hasAnyRole(['doctor', 'nurse'])) {
            $patients = Patient::where('doctor_id', $user->id)->get();
        } else {
            $patients = Patient::all()->map(function($p) {
                // Return only basic info
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'limited_info' => $p->limited_info
                ];
            });
        }

        return response()->json($patients);
    }

    public function show(Patient $patient)
    {
        Gate::authorize('view', $patient);

        $response = [
            'id' => $patient->id,
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'limited_info' => $patient->limited_info,
        ];

        // HIPAA rule: only authorized users can see full medical history
        if (Gate::allows('viewMedicalHistory', $patient)) {
            $response['medical_history'] = $patient->medical_history;
        }

        return response()->json($response);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Patient::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'limited_info' => 'nullable|string',
            'medical_history' => 'nullable|string',
            'doctor_id' => 'nullable|integer|exists:users,id',
        ]);

        $patient = Patient::create($validated);

        return response()->json([
            'message' => 'Patient created successfully',
            'patient' => $patient
        ], 201);
    }

    public function update(Request $request, Patient $patient)
    {
        Gate::authorize('update', $patient);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'limited_info' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        // If the user cannot view medical history, they shouldn't be allowed to edit it
        if (isset($validated['medical_history']) && !Gate::allows('viewMedicalHistory', $patient)) {
            unset($validated['medical_history']);
        }

        $patient->update($validated);

        return response()->json([
            'message' => 'Patient updated successfully',
            'patient' => $patient
        ]);
    }

    public function destroy(Patient $patient)
    {
        Gate::authorize('delete', $patient);

        $patient->delete();

        return response()->json([
            'message' => 'Patient deleted successfully'
        ]);
    }
}
