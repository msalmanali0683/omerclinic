<?php

namespace App\Services;

use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LaboratoryPatientService
{
    public function search(Request $request): array
    {
        $visitFilter = $request->get('visit_filter', 'latest');
        $search = $request->get('search');
        $limit = min(max((int) $request->get('limit', 50), 1), 100);

        $patientsQuery = Patient::query()
            ->with(['visits' => function ($query) {
                $query->with('doctor')
                    ->whereIn('status', [
                        PatientVisit::STATUS_PENDING,
                        PatientVisit::STATUS_IN_CONSULTATION,
                        PatientVisit::STATUS_PRESCRIBED,
                        PatientVisit::STATUS_COMPLETED,
                    ])
                    ->orderByDesc('visit_date')
                    ->orderByDesc('visit_time')
                    ->orderByDesc('id');
            }]);

        if ($search) {
            $patientsQuery->where(function (Builder $q) use ($search) {
                $q->where('mr_number', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_father_name', 'like', "%{$search}%")
                    ->orWhere('patient_cell', 'like', "%{$search}%")
                    ->orWhere('patient_cnic', 'like', "%{$search}%");
            });
        }

        $patients = $patientsQuery
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        if ($visitFilter === 'all') {
            return $patients->flatMap(fn (Patient $patient) => $this->expandAllVisits($patient))->values()->all();
        }

        return $patients->map(fn (Patient $patient) => $this->formatLatestRow($patient))->values()->all();
    }

    protected function formatLatestRow(Patient $patient): array
    {
        $latestVisit = $patient->visits->first();

        return [
            'patient' => $this->formatPatient($patient),
            'visit'   => $latestVisit ? $this->formatVisit($latestVisit) : null,
            'has_visits' => $patient->visits->isNotEmpty(),
            'no_visit_message' => $patient->visits->isEmpty()
                ? 'No visit found - report will be linked to patient only.'
                : null,
        ];
    }

    protected function expandAllVisits(Patient $patient): array
    {
        if ($patient->visits->isEmpty()) {
            return [[
                'patient'          => $this->formatPatient($patient),
                'visit'            => null,
                'has_visits'       => false,
                'no_visit_message' => 'No visit found - report will be linked to patient only.',
            ]];
        }

        return $patient->visits->map(fn (PatientVisit $visit) => [
            'patient'          => $this->formatPatient($patient),
            'visit'            => $this->formatVisit($visit),
            'has_visits'       => true,
            'no_visit_message' => null,
        ])->all();
    }

    protected function formatPatient(Patient $patient): array
    {
        return [
            'id'                  => $patient->id,
            'mr_number'           => $patient->mr_number,
            'patient_name'        => $patient->patient_name,
            'patient_father_name' => $patient->patient_father_name,
            'patient_gender'      => $patient->patient_gender,
            'patient_age'         => $patient->patient_age,
            'patient_age_unit'    => $patient->patient_age_unit,
            'patient_cell'      => $patient->patient_cell,
            'patient_cnic'      => $patient->patient_cnic,
        ];
    }

    protected function formatVisit(PatientVisit $visit): array
    {
        return [
            'id'          => $visit->id,
            'visit_date'  => $visit->visit_date?->format('Y-m-d'),
            'visit_time'  => $visit->visit_time,
            'status'      => $visit->status,
            'doctor_id'   => $visit->doctor_id,
            'doctor_name' => $visit->doctor?->name,
        ];
    }
}
