<?php

namespace App\Http\Controllers;

use App\Models\LabReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LabReportController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', LabReport::class);

        $user = auth()->user();
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin') || $user->hasAnyRole(['lab-technician', 'lab-manager'])) {
            $reports = LabReport::with('patient', 'doctor')->get();
        } elseif ($user->hasRole('doctor')) {
            $reports = LabReport::with('patient')->where('doctor_id', $user->id)->get();
        } else {
            // Patients can see only approved reports
            $reports = LabReport::with('doctor')->where('is_approved', true)->whereHas('patient', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        }

        return response()->json($reports);
    }

    public function show(LabReport $labReport)
    {
        Gate::authorize('view', $labReport);

        return response()->json($labReport->load('patient', 'doctor'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', LabReport::class);

        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'doctor_id' => 'required|integer|exists:users,id',
            'test_name' => 'required|string|max:255',
            'report_data' => 'required|string',
        ]);

        $validated['technician_id'] = auth()->id();

        $labReport = LabReport::create($validated);

        return response()->json([
            'message' => 'Lab report created successfully',
            'lab_report' => $labReport
        ], 201);
    }

    public function update(Request $request, LabReport $labReport)
    {
        Gate::authorize('update', $labReport);

        $validated = $request->validate([
            'test_name' => 'sometimes|required|string|max:255',
            'report_data' => 'sometimes|required|string',
        ]);

        $labReport->update($validated);

        return response()->json([
            'message' => 'Lab report updated successfully',
            'lab_report' => $labReport
        ]);
    }

    public function approve(LabReport $labReport)
    {
        Gate::authorize('approve', $labReport);

        $labReport->update([
            'is_approved' => true,
            'approved_at' => now(),
            'manager_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Lab report approved successfully',
            'lab_report' => $labReport
        ]);
    }
}
