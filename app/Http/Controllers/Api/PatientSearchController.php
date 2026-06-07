<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\Patient;
use App\Models\PatientVisit;
use Illuminate\Http\Request;

class PatientSearchController extends Controller
{
    public function search(Request $request)
    {
        $this->authorize('search', Patient::class);

        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $term = $request->q;

        $patients = Patient::query()
            ->withInQueueTodayFlag()
            ->where(function ($query) use ($term) {
                $query->where('mr_number', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%")
                    ->orWhere('patient_cell', 'like', "%{$term}%")
                    ->orWhere('patient_cnic', 'like', "%{$term}%");
            })
            ->latest()
            ->limit(25)
            ->get();

        return PatientResource::collection($patients);
    }

    public function searchVisits(Request $request)
    {
        $this->authorize('search', Patient::class);

        $request->validate([
            'q'        => 'nullable|string|max:100',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PatientVisit::query()
            ->with(['patient' => fn ($patientQuery) => $patientQuery->withInQueueTodayFlag(), 'doctor', 'token'])
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $term = $request->q;

            $query->whereHas('patient', function ($patientQuery) use ($term) {
                $patientQuery->where('mr_number', 'like', "%{$term}%")
                    ->orWhere('patient_name', 'like', "%{$term}%")
                    ->orWhere('patient_cell', 'like', "%{$term}%")
                    ->orWhere('patient_cnic', 'like', "%{$term}%");
            });
        }

        return PatientVisitResource::collection(
            $query->paginate($request->integer('per_page', 25))
        );
    }
}
