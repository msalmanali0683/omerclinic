<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\ClinicalScan;
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

        $scannableStatuses = [
            PatientVisit::STATUS_PENDING,
            PatientVisit::STATUS_IN_CONSULTATION,
        ];

        $statuses = $scannableStatuses;
        if ($request->filled('status')) {
            $requested = array_values(array_filter(array_map('trim', explode(',', $request->status))));
            $filtered = array_values(array_intersect($requested, $scannableStatuses));

            if ($filtered !== []) {
                $statuses = $filtered;
            }
        }

        $query = PatientVisit::query()
            ->with(['patient', 'doctor'])
            ->withCount([
                'clinicalScans as completed_scans_count' => function ($q) {
                    $q->where('status', ClinicalScan::STATUS_COMPLETED);
                },
            ])
            ->whereIn('status', $statuses)
            ->whereDoesntHave('prescription');

        if ($request->boolean('today_only')) {
            $query->whereDate('visit_date', $request->get('date', now()->toDateString()));
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
            ->limit($request->get('limit', 100))
            ->get();

        $data = $visits->map(fn (PatientVisit $visit) => [
            'patient'                    => (new PatientResource($visit->patient))->resolve(),
            'visit'                      => (new PatientVisitResource($visit))->resolve(),
            'has_prescription'           => false,
            'has_completed_scan_on_visit'  => ($visit->completed_scans_count ?? 0) > 0,
            'completed_scans_count'      => (int) ($visit->completed_scans_count ?? 0),
        ])->values();

        return response()->json(['data' => $data]);
    }
}
