<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\PatientVisit;
use Illuminate\Http\Request;

class ClinicalScanQueueSearchController extends Controller
{
    public function search(Request $request)
    {
        abort_unless(
            $request->user()->can('search queue patients for scan')
            || $request->user()->can('create clinical scans'),
            403
        );

        $query = PatientVisit::query()
            ->with(['patient', 'doctor'])
            ->whereIn('status', [
                PatientVisit::STATUS_PENDING,
                PatientVisit::STATUS_IN_CONSULTATION,
            ])
            ->whereDoesntHave('prescription');

        $date = $request->get('date', now()->toDateString());
        if ($request->boolean('today_only', true) && ! $request->filled('date')) {
            $query->whereDate('visit_date', $date);
        } elseif ($request->filled('date')) {
            $query->whereDate('visit_date', $request->date);
        }

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('mr_number', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_cell', 'like', "%{$search}%")
                    ->orWhere('patient_cnic', 'like', "%{$search}%");
            });
        }

        $visits = $query
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->orderByDesc('id')
            ->limit($request->get('limit', 50))
            ->get();

        $data = $visits->map(fn (PatientVisit $visit) => [
            'patient'         => (new PatientResource($visit->patient))->resolve(),
            'visit'           => (new PatientVisitResource($visit))->resolve(),
            'has_prescription' => false,
        ])->values();

        return response()->json(['data' => $data]);
    }
}
