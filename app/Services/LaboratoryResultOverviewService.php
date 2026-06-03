<?php

namespace App\Services;

use App\Models\LaboratoryResult;
use App\Models\Patient;
use App\Models\PatientVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaboratoryResultOverviewService
{
    public function patientsIndex(array $filters): Collection
    {
        $patientIds = LaboratoryResult::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereHas('patient', function (Builder $patientQuery) use ($search) {
                    $patientQuery->where('mr_number', 'like', "%{$search}%")
                        ->orWhere('patient_name', 'like', "%{$search}%")
                        ->orWhere('patient_father_name', 'like', "%{$search}%")
                        ->orWhere('patient_cell', 'like', "%{$search}%");
                });
            })
            ->distinct()
            ->pluck('patient_id');

        return Patient::query()
            ->whereIn('id', $patientIds)
            ->orderBy('patient_name')
            ->get()
            ->map(fn (Patient $patient) => $this->summarizePatient($patient));
    }

    public function patientVisitsSummary(Patient $patient): array
    {
        $results = LaboratoryResult::query()
            ->where('patient_id', $patient->id)
            ->get();

        $visitIds = $results->whereNotNull('patient_visit_id')->pluck('patient_visit_id')->unique();

        $visits = PatientVisit::query()
            ->with('doctor')
            ->whereIn('id', $visitIds)
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->get()
            ->map(function (PatientVisit $visit) use ($results) {
                $visitResults = $results->where('patient_visit_id', $visit->id);

                return [
                    'id'                    => $visit->id,
                    'visit_no'              => 'V-'.$visit->id,
                    'visit_date'            => $visit->visit_date?->format('Y-m-d'),
                    'visit_time'            => $visit->visit_time,
                    'doctor_name'           => $visit->doctor?->name,
                    'tests_count'           => $visitResults->count(),
                    'draft_tests_count'     => $visitResults->where('status', LaboratoryResult::STATUS_DRAFT)->count(),
                    'completed_tests_count' => $visitResults->whereIn('status', [
                        LaboratoryResult::STATUS_COMPLETED,
                        LaboratoryResult::STATUS_VERIFIED,
                    ])->count(),
                ];
            })
            ->values();

        $noVisitResults = $results->whereNull('patient_visit_id');

        $latestVisitDate = $visits->first()['visit_date'] ?? null;

        return [
            'patient' => [
                'id'            => $patient->id,
                'patient_name'  => $patient->patient_name,
                'patient_code'  => $patient->mr_number,
                'phone'         => $patient->patient_cell,
            ],
            'visits'  => $visits,
            'no_visit' => [
                'tests_count'           => $noVisitResults->count(),
                'draft_tests_count'     => $noVisitResults->where('status', LaboratoryResult::STATUS_DRAFT)->count(),
                'completed_tests_count' => $noVisitResults->whereIn('status', [
                    LaboratoryResult::STATUS_COMPLETED,
                    LaboratoryResult::STATUS_VERIFIED,
                ])->count(),
                'latest_date'           => $noVisitResults->max('created_at')?->format('Y-m-d H:i'),
            ],
            'latest_visit_date' => $latestVisitDate,
        ];
    }

    public function testsForVisit(Patient $patient, ?PatientVisit $visit): array
    {
        $query = LaboratoryResult::query()
            ->with(['bill', 'labOperator', 'template'])
            ->where('patient_id', $patient->id);

        if ($visit) {
            $query->where('patient_visit_id', $visit->id);
        } else {
            $query->whereNull('patient_visit_id');
        }

        $results = $query->latest('created_at')->get();

        $laboratoryBills = [];
        if ($visit) {
            $laboratoryBills = $visit->laboratoryBills()
                ->orderByDesc('id')
                ->get(['id', 'bill_no', 'status', 'total', 'created_at'])
                ->map(fn ($bill) => [
                    'id'         => $bill->id,
                    'bill_no'    => $bill->bill_no,
                    'status'     => $bill->status,
                    'total'      => $bill->total,
                    'created_at' => $bill->created_at?->format('Y-m-d H:i'),
                ])
                ->values()
                ->all();
        }

        return [
            'patient' => [
                'id'           => $patient->id,
                'name'         => $patient->patient_name,
                'patient_code' => $patient->mr_number,
            ],
            'visit' => $visit ? [
                'id'         => $visit->id,
                'visit_no'   => 'V-'.$visit->id,
                'visit_date' => $visit->visit_date?->format('Y-m-d'),
            ] : null,
            'visit_label'      => $visit ? 'Visit #'.$visit->id : 'No Visit',
            'tests'            => $results->map(fn (LaboratoryResult $result) => $this->formatTestRow($result))->values()->all(),
            'laboratory_bills' => $laboratoryBills,
        ];
    }

    protected function summarizePatient(Patient $patient): array
    {
        $results = LaboratoryResult::query()->where('patient_id', $patient->id)->get();
        $visitIds = $results->whereNotNull('patient_visit_id')->pluck('patient_visit_id')->unique();

        $latestVisit = $visitIds->isNotEmpty()
            ? PatientVisit::query()
                ->whereIn('id', $visitIds)
                ->orderByDesc('visit_date')
                ->orderByDesc('visit_time')
                ->first()
            : null;

        $noVisitCount = $results->whereNull('patient_visit_id')->count();

        return [
            'patient_id'              => $patient->id,
            'patient_name'            => $patient->patient_name,
            'patient_code'            => $patient->mr_number,
            'phone'                   => $patient->patient_cell,
            'latest_visit_date'       => $latestVisit?->visit_date?->format('Y-m-d'),
            'visits_count'            => $visitIds->count(),
            'no_visit_tests_count'    => $noVisitCount,
            'tests_count'             => $results->count(),
            'draft_tests_count'       => $results->where('status', LaboratoryResult::STATUS_DRAFT)->count(),
            'completed_tests_count'   => $results->whereIn('status', [
                LaboratoryResult::STATUS_COMPLETED,
                LaboratoryResult::STATUS_VERIFIED,
            ])->count(),
        ];
    }

    protected function formatTestRow(LaboratoryResult $result): array
    {
        return [
            'id'         => $result->id,
            'test_name'  => $result->test_name,
            'status'     => $result->status,
            'test_price' => $result->test_price,
            'bill_no'    => $result->bill?->bill_no,
            'visit_label'=> $result->patient_visit_id ? 'Visit #'.$result->patient_visit_id : 'No Visit',
            'created_at' => $result->created_at?->format('Y-m-d H:i'),
        ];
    }
}
