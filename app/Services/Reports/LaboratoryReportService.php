<?php

namespace App\Services\Reports;

use App\Models\LaboratoryResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class LaboratoryReportService
{
    public const EXPORT_LIMIT = 2000;

    public function getPaginated(array $filters, User $user): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);

        return $this->buildQuery($filters, $user)
            ->paginate($perPage)
            ->through(fn ($row) => $this->formatRow($row));
    }

    public function getForExport(array $filters, User $user): Collection
    {
        return $this->buildQuery($filters, $user)
            ->limit(self::EXPORT_LIMIT + 1)
            ->get()
            ->map(fn ($row) => $this->formatRow($row));
    }

    public function exceedsExportLimit(array $filters, User $user): bool
    {
        return $this->buildQuery($filters, $user)->count() > self::EXPORT_LIMIT;
    }

    public function buildPrintPayload(array $filters, User $user): array
    {
        if ($this->exceedsExportLimit($filters, $user)) {
            throw new InvalidArgumentException('Too many records for export. Please narrow filters.');
        }

        $rows = $this->getForExport($filters, $user)->take(self::EXPORT_LIMIT);
        $summary = $this->buildSummary($rows);

        return [
            'title'          => 'Laboratory Report',
            'hospital_name'       => config('hospital.name'),
            'report_footer_lines' => config('hospital.lab_report_footer'),
            'generated_at'   => now()->format('Y-m-d H:i:s'),
            'generated_by'   => $user->name,
            'filters'        => $this->describeFilters($filters),
            'summary'        => $summary,
            'rows'           => $rows->values()->all(),
            'patient_groups' => $this->buildPatientGroups($rows),
            'grand_total'    => $summary['grand_total_price'],
        ];
    }

    public function buildSummary(?Collection $rows = null, array $filters = [], ?User $user = null): array
    {
        if ($rows === null && $user !== null) {
            $rows = $this->buildQuery($filters, $user)
                ->get()
                ->map(fn ($row) => $this->formatRow($row));
        }

        $rows ??= collect();

        return [
            'total_results'      => $rows->count(),
            'total_patients'     => $rows->pluck('patient_id')->filter()->unique()->count(),
            'grand_total_price'  => round((float) $rows->sum('test_price'), 2),
        ];
    }

    public function normalizeFilters(array $input): array
    {
        return [
            'from_date'           => $input['from_date'] ?? null,
            'to_date'             => $input['to_date'] ?? null,
            'mr_number'           => $input['mr_number'] ?? null,
            'patient_name'        => $input['patient_name'] ?? null,
            'patient_father_name' => $input['patient_father_name'] ?? null,
            'patient_gender'      => $input['patient_gender'] ?? null,
            'test_name'           => $input['test_name'] ?? null,
            'test_code'           => $input['test_code'] ?? null,
            'status'              => $input['status'] ?? null,
            'doctor_id'           => $input['doctor_id'] ?? null,
            'search'              => $input['search'] ?? null,
            'per_page'            => $input['per_page'] ?? 25,
            'page'                => $input['page'] ?? 1,
        ];
    }

    protected function buildQuery(array $filters, User $user): Builder
    {
        $query = LaboratoryResult::query()
            ->with(['patient', 'visit.doctor'])
            ->orderBy('laboratory_results.result_date')
            ->orderBy('laboratory_results.result_time')
            ->orderBy('laboratory_results.id');

        $this->applyDoctorScope($query, $user);
        $this->applyFilters($query, $filters);

        return $query;
    }

    protected function applyDoctorScope(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return;
        }

        if ($user->hasRole('doctor') && $user->can('view laboratory reports')) {
            $query->whereHas('visit', fn (Builder $visitQuery) => $visitQuery->where('doctor_id', $user->id));
        }
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['from_date'] ?? null) {
            $query->whereDate('laboratory_results.result_date', '>=', $filters['from_date']);
        }

        if ($filters['to_date'] ?? null) {
            $query->whereDate('laboratory_results.result_date', '<=', $filters['to_date']);
        }

        if ($filters['status'] ?? null) {
            $query->where('laboratory_results.status', $filters['status']);
        }

        if ($filters['test_name'] ?? null) {
            $query->where('laboratory_results.test_name', 'like', '%'.$filters['test_name'].'%');
        }

        if ($filters['test_code'] ?? null) {
            $query->where('laboratory_results.test_code', 'like', '%'.$filters['test_code'].'%');
        }

        if ($filters['doctor_id'] ?? null) {
            $query->whereHas('visit', fn (Builder $visitQuery) => $visitQuery->where('doctor_id', $filters['doctor_id']));
        }

        if ($filters['mr_number'] ?? null) {
            $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('patients.mr_number', 'like', '%'.$filters['mr_number'].'%'));
        }

        if ($filters['patient_name'] ?? null) {
            $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('patients.patient_name', 'like', '%'.$filters['patient_name'].'%'));
        }

        if ($filters['patient_father_name'] ?? null) {
            $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('patients.patient_father_name', 'like', '%'.$filters['patient_father_name'].'%'));
        }

        if ($filters['patient_gender'] ?? null) {
            $query->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('patients.patient_gender', $filters['patient_gender']));
        }

        if ($filters['search'] ?? null) {
            $search = $filters['search'];
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->where('laboratory_results.test_name', 'like', "%{$search}%")
                    ->orWhere('laboratory_results.test_code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $patientQuery) use ($search) {
                        $patientQuery->where('patients.mr_number', 'like', "%{$search}%")
                            ->orWhere('patients.patient_name', 'like', "%{$search}%")
                            ->orWhere('patients.patient_father_name', 'like', "%{$search}%");
                    });
            });
        }
    }

    protected function formatRow(LaboratoryResult $result): array
    {
        $patient = $result->patient;

        return [
            'result_id'           => $result->id,
            'patient_id'          => $patient?->id,
            'mr_number'           => $patient?->mr_number,
            'patient_name'        => $patient?->patient_name,
            'patient_father_name' => $patient?->patient_father_name,
            'test_name'           => $result->test_name,
            'test_code'           => $result->test_code,
            'test_price'          => (float) ($result->test_price ?? 0),
            'result_date'         => $result->result_date?->format('Y-m-d'),
            'result_time'         => $result->result_time,
            'status'              => $result->status,
            'visit_id'            => $result->patient_visit_id,
            'doctor_name'         => $result->visit?->doctor?->name,
        ];
    }

    protected function buildPatientGroups(Collection $rows): array
    {
        return $rows
            ->groupBy('patient_id')
            ->map(function (Collection $patientRows) {
                $first = $patientRows->first();

                $tests = $patientRows->map(fn (array $row) => [
                    'result_id'   => $row['result_id'],
                    'test_name'   => $row['test_name'],
                    'test_price'  => $row['test_price'],
                    'result_date' => $row['result_date'],
                ])->values()->all();

                $patientTotal = round((float) $patientRows->sum('test_price'), 2);

                return [
                    'patient_id'          => $first['patient_id'],
                    'mr_number'           => $first['mr_number'],
                    'patient_name'        => $first['patient_name'],
                    'patient_father_name' => $first['patient_father_name'],
                    'tests'               => $tests,
                    'patient_total'       => $patientTotal,
                ];
            })
            ->values()
            ->all();
    }

    protected function describeFilters(array $filters): array
    {
        $labels = [];

        foreach ([
            'from_date'           => 'From Date',
            'to_date'             => 'To Date',
            'mr_number'           => 'MR Number',
            'patient_name'        => 'Patient Name',
            'patient_father_name' => 'Father Name',
            'patient_gender'      => 'Gender',
            'test_name'           => 'Test Name',
            'test_code'           => 'Test Code',
            'status'              => 'Status',
            'doctor_id'           => 'Doctor',
            'search'              => 'Search',
        ] as $key => $label) {
            if (! empty($filters[$key])) {
                $labels[$label] = (string) $filters[$key];
            }
        }

        return $labels;
    }
}
