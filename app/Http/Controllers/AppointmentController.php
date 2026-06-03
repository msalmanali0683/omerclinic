<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Appointment::class);

        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin') || $user->hasRole('receptionist')) {
            $appointments = Appointment::with('patient', 'doctor')->get();
        } elseif ($user->hasRole('doctor')) {
            $appointments = Appointment::with('patient')->where('doctor_id', $user->id)->get();
        } else {
            $appointments = Appointment::with('doctor')->whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        }

        return response()->json($appointments);
    }

    public function show(Appointment $appointment)
    {
        Gate::authorize('view', $appointment);

        return response()->json($appointment->load('patient', 'doctor'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Appointment::class);

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'required|integer|exists:users,id',
            'appointment_date' => 'required|date',
            'status' => 'nullable|string',
        ]);

        $validated['receptionist_id'] = auth()->id();

        $appointment = Appointment::create($validated);

        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment
        ], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        Gate::authorize('update', $appointment);

        $validated = $request->validate([
            'appointment_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|string',
            'doctor_id' => 'sometimes|required|integer|exists:users,id',
        ]);

        $appointment->update($validated);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    public function destroy(Appointment $appointment)
    {
        Gate::authorize('delete', $appointment);

        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'appointment' => $appointment
        ]);
    }
}
