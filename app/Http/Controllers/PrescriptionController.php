<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PrescriptionController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Prescription::class);

        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin') || $user->hasRole('pharmacist')) {
            $prescriptions = Prescription::with('patient', 'doctor')->get();
        } elseif ($user->hasRole('doctor')) {
            $prescriptions = Prescription::with('patient')->where('doctor_id', $user->id)->get();
        } else {
            $prescriptions = Prescription::with('doctor')->whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        }

        return response()->json($prescriptions);
    }

    public function show(Prescription $prescription)
    {
        Gate::authorize('view', $prescription);

        return response()->json($prescription->load('patient', 'doctor'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Prescription::class);

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'diagnosis' => 'required|string',
            'medicines' => 'required|string',
        ]);

        $validated['doctor_id'] = auth()->id();

        $prescription = Prescription::create($validated);

        return response()->json([
            'message' => 'Prescription created successfully',
            'prescription' => $prescription
        ], 201);
    }

    public function update(Request $request, Prescription $prescription)
    {
        Gate::authorize('update', $prescription);

        $validated = $request->validate([
            'diagnosis' => 'sometimes|required|string',
            'medicines' => 'sometimes|required|string',
        ]);

        $prescription->update($validated);

        return response()->json([
            'message' => 'Prescription updated successfully',
            'prescription' => $prescription
        ]);
    }

    public function dispense(Prescription $prescription)
    {
        Gate::authorize('dispense', $prescription);

        $prescription->update(['is_dispensed' => true]);

        return response()->json([
            'message' => 'Medicine dispensed successfully',
            'prescription' => $prescription
        ]);
    }
}
