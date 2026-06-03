<?php

namespace App\Services\Reports;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PatientReportService
{
    public const EXPORT_LIMIT = 2000;

    public function getPaginated(array $filters, User $user): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);

        return $this->buildQuery($filters, $user)
            ->paginate($perPage)
            ->through(fn ($row) => $this->formatRow($row, $filters, $user));
    }

    public function getForExport(array $filters, User $user): Collection
    {
        return $this->buildQuery($filters, $user)
            ->limit(self::EXPORT_LIMIT + 1)
            ->get()
            ->map(fn ($row) => $this->formatRow($row, $filters, $user));
    }

    public function exceedsExportLimit(array $filters, User $user): bool
    {
        return $this->buildQuery($filters, $user)->count() > self::EXPORT_LIMIT;
    }

    public function buildPrintPayload(array $filters, User $user): array
    {
        if ($this->exceedsExportLimit($filters, $user)) {
            throw new InvalidArgumentException('Too many records for PDF export. Please narrow filters.');
        }

        $rows = $this->getForExport($filters, $user)->take(self::EXPORT_LIMIT);

        return [
            'title'         => 'Patient Report',
            'hospital_name' => config('app.name'),
            'generated_at'  => now()->format('Y-m-d H:i:s'),
            'generated_by'  => $user->name,
            'filters'       => $this->describeFilters($filters),
            'summary'       => $this->buildSummary($filters, $user, $rows),
            'rows'          => $rows->values()->all(),
            'report_type'   => $filters['report_type'] ?? 'patient',
        ];
    }

    public function buildSummary(array $filters, User $user, ?Collection $rows = null): array
    {
        if ($rows === null) {
            $rows = $this->buildQuery($filters, $user)
                ->get()
                ->map(fn ($row) => $this->formatRow($row, $filters, $user));
        }

        $reportType = $filters['report_type'] ?? 'patient';

        return [
            'total_patients'         => $reportType === 'visit'
                ? $rows->pluck('patient_id')->filter()->unique()->count()
                : $rows->count(),
            'total_visits'           => $reportType === 'visit'
                ? $rows->count()
                : (int) $rows->sum('total_visits'),
            'male_count'             => $rows->where('patient_gender', 'male')->count(),
            'female_count'           => $rows->where('patient_gender', 'female')->count(),
            'other_count'            => $rows->where('patient_gender', 'other')->count(),
            'pending_visit_count'    => $rows->filter(
                fn ($row) => ($row['latest_visit']['status'] ?? null) === PatientVisit::STATUS_PENDING
            )->count(),
            'prescribed_visit_count' => $rows->filter(
                fn ($row) => ($row['latest_visit']['status'] ?? null) === PatientVisit::STATUS_PRESCRIBED
            )->count(),
        ];
    }

    public function normalizeFilters(array $input): array
    {
        return [
            'report_type'           => in_array(($input['report_type'] ?? 'patient'), ['patient', 'visit'], true)
                ? ($input['report_type'] ?? 'patient')
                : 'patient',
            'filter_by'             => in_array(($input['filter_by'] ?? 'registration_date'), ['registration_date', 'visit_date'], true)
                ? ($input['filter_by'] ?? 'registration_date')
                : 'registration_date',
            'from_date'             => $input['from_date'] ?? null,
            'to_date'               => $input['to_date'] ?? null,
            'mr_number'             => $input['mr_number'] ?? null,
            'patient_name'          => $input['patient_name'] ?? null,
            'patient_father_name'   => $input['patient_father_name'] ?? null,
            'patient_gender'        => $input['patient_gender'] ?? null,
            'age_from'              => $input['age_from'] ?? null,
            'age_to'                => $input['age_to'] ?? null,
            'age_unit'              => $input['age_unit'] ?? null,
            'patient_cell'          => $input['patient_cell'] ?? null,
            'patient_cnic'          => $input['patient_cnic'] ?? null,
            'status'                => $input['status'] ?? null,
            'doctor_id'             => $input['doctor_id'] ?? null,
            'has_prescription'      => $input['has_prescription'] ?? null,
            'has_laboratory_result' => $input['has_laboratory_result'] ?? null,
            'has_clinical_scan'     => $input['has_clinical_scan'] ?? null,
            'search'                => $input['search'] ?? null,
            'per_page'              => $input['per_page'] ?? 25,
            'page'                  => $input['page'] ?? 1,
        ];
    }

    protected function buildQuery(array $filters, User $user): Builder
    {
        return ($filters['report_type'] ?? 'patient') === 'visit'
            ? $this->buildVisitQuery($filters, $user)
            : $this->buildPatientQuery($filters, $user);
    }

    protected function buildPatientQuery(array $filters, User $user): Builder
    {
        $query = Patient::query()
            ->withCount('visits')
            ->with(['latestVisit.doctor', 'latestVisit.prescription'])
            ->withExists([
                'prescriptions as has_prescription_exists',
                'laboratoryResults as has_laboratory_result_exists',
                'clinicalScans as has_clinical_scan_exists',
            ]);

        $this->applyDoctorScope($query, $user, 'patient');
        $this->applyCommonPatientFieldFilters($query, $filters);
        $this->applyDateFilters($query, $filters, 'patient');
        $this->applyAgeFilters($query, $filters);
        $this->applyVisitRelatedFilters($query, $filters, 'patient');
        $this->applySearchFilter($query, $filters);

        $query->orderByDesc('patients.created_at');

        if (($filters['filter_by'] ?? 'registration_date') === 'visit_date') {
            $query->orderByDesc(
                PatientVisit::select('visit_date')
                    ->whereColumn('patient_visits.patient_id', 'patients.id')
                    ->orderByDesc('visit_date')
                    ->orderByDesc('visit_time')
                    ->limit(1)
            );
        }

        return $query;
    }

    protected function buildVisitQuery(array $filters, User $user): Builder
    {
        $query = PatientVisit::query()
            ->with(['patient', 'doctor', 'prescription'])
            ->withExists([
                'laboratoryResults as has_laboratory_result_exists',
                'clinicalScans as has_clinical_scan_exists',
            ]);

        $this->applyDoctorScope($query, $user, 'visit');
        $this->applyVisitFilters($query, $filters);
        $this->applyDateFilters($query, $filters, 'visit');
        $this->applyVisitRelatedFilters($query, $filters, 'visit');

        return $query->orderByDesc('patient_visits.visit_date')
            ->orderByDesc('patient_visits.visit_time')
            ->orderByDesc('patient_visits.created_at');
    }

    protected function applyDoctorScope(Builder $query, User $user, string $mode): void
    {
        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return;
        }

        if ($user->hasRole('doctor') && $user->can('view patient reports')) {
            if ($mode === 'visit') {
                $query->where('patient_visits.doctor_id', $user->id);
            } else {
                $query->whereHas('visits', fn (Builder $visitQuery) => $visitQuery->where('doctor_id', $user->id));
            }
        }
    }

    protected function applyCommonPatientFieldFilters(Builder $query, array $filters): void
    {
        if ($filters['mr_number'] ?? null) {
            $query->where('patients.mr_number', 'like', '%'.$filters['mr_number'].'%');
        }

        if ($filters['patient_name'] ?? null) {
            $query->where('patients.patient_name', 'like', '%'.$filters['patient_name'].'%');
        }

        if ($filters['patient_father_name'] ?? null) {
            $query->where('patients.patient_father_name', 'like', '%'.$filters['patient_father_name'].'%');
        }

        if ($filters['patient_gender'] ?? null) {
            $query->where('patients.patient_gender', $filters['patient_gender']);
        }

        if ($filters['patient_cell'] ?? null) {
            $query->where('patients.patient_cell', 'like', '%'.$filters['patient_cell'].'%');
        }

        if ($filters['patient_cnic'] ?? null) {
            $query->where('patients.patient_cnic', 'like', '%'.$filters['patient_cnic'].'%');
        }
    }

    protected function applyVisitFilters(Builder $query, array $filters): void
    {
        $query->whereHas('patient', function (Builder $patientQuery) use ($filters) {
            $this->applyCommonPatientFieldFilters($patientQuery, $filters);
            $this->applyAgeFilters($patientQuery, $filters);
            $this->applySearchFilter($patientQuery, $filters);
        });

        if ($filters['status'] ?? null) {
            $query->where('patient_visits.status', $filters['status']);
        }

        if ($filters['doctor_id'] ?? null) {
            $query->where('patient_visits.doctor_id', $filters['doctor_id']);
        }
    }

    protected function applyDateFilters(Builder $query, array $filters, string $mode): void
    {
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if (! $fromDate && ! $toDate) {
            return;
        }

        $filterBy = $filters['filter_by'] ?? 'registration_date';

        if ($mode === 'patient') {
            if ($filterBy === 'registration_date') {
                if ($fromDate) {
                    $query->whereDate('patients.created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->whereDate('patients.created_at', '<=', $toDate);
                }

                return;
            }

            $query->whereHas('visits', function (Builder $visitQuery) use ($fromDate, $toDate) {
                if ($fromDate) {
                    $visitQuery->whereDate('visit_date', '>=', $fromDate);
                }
                if ($toDate) {
                    $visitQuery->whereDate('visit_date', '<=', $toDate);
                }
            });

            return;
        }

        if ($fromDate) {
            $query->whereDate('patient_visits.visit_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('patient_visits.visit_date', '<=', $toDate);
        }
    }

    protected function applyAgeFilters(Builder $query, array $filters): void
    {
        $ageUnit = $filters['age_unit'] ?? null;

        if ($ageUnit) {
            $query->where('patients.patient_age_unit', $ageUnit);
        }

        if ($filters['age_from'] ?? null) {
            $query->where('patients.patient_age', '>=', (int) $filters['age_from']);
        }

        if ($filters['age_to'] ?? null) {
            $query->where('patients.patient_age', '<=', (int) $filters['age_to']);
        }
    }

    protected function applyVisitRelatedFilters(Builder $query, array $filters, string $mode): void
    {
        if ($filters['status'] ?? null) {
            if ($mode === 'patient') {
                $query->whereHas('latestVisit', fn (Builder $visitQuery) => $visitQuery->where('status', $filters['status']));
            }
        }

        if ($filters['doctor_id'] ?? null) {
            if ($mode === 'patient') {
                $query->whereHas('visits', fn (Builder $visitQuery) => $visitQuery->where('doctor_id', $filters['doctor_id']));
            }
        }

        $this->applyBooleanRelationFilter($query, $filters, 'has_prescription', 'prescriptions', $mode);
        $this->applyBooleanRelationFilter($query, $filters, 'has_laboratory_result', 'laboratoryResults', $mode);
        $this->applyBooleanRelationFilter($query, $filters, 'has_clinical_scan', 'clinicalScans', $mode);
    }

    protected function applyBooleanRelationFilter(
        Builder $query,
        array $filters,
        string $key,
        string $relation,
        string $mode
    ): void {
        $value = $filters[$key] ?? null;

        if (! in_array($value, ['yes', 'no'], true)) {
            return;
        }

        if ($mode === 'visit') {
            if ($key === 'has_prescription') {
                $value === 'yes'
                    ? $query->whereHas('prescription')
                    : $query->whereDoesntHave('prescription');

                return;
            }

            $existsColumn = match ($relation) {
                'laboratoryResults' => 'has_laboratory_result_exists',
                'clinicalScans'     => 'has_clinical_scan_exists',
                default             => null,
            };

            if ($existsColumn) {
                $value === 'yes'
                    ? $query->whereHas($relation)
                    : $query->whereDoesntHave($relation);
            }

            return;
        }

        $value === 'yes'
            ? $query->whereHas($relation)
            : $query->whereDoesntHave($relation);
    }

    protected function applySearchFilter(Builder $query, array $filters): void
    {
        if (! ($filters['search'] ?? null)) {
            return;
        }

        $search = $filters['search'];

        $query->where(function (Builder $searchQuery) use ($search) {
            $searchQuery->where('patients.mr_number', 'like', "%{$search}%")
                ->orWhere('patients.patient_name', 'like', "%{$search}%")
                ->orWhere('patients.patient_father_name', 'like', "%{$search}%")
                ->orWhere('patients.patient_cell', 'like', "%{$search}%")
                ->orWhere('patients.patient_cnic', 'like', "%{$search}%")
                ->orWhere('patients.patient_address', 'like', "%{$search}%");
        });
    }

    protected function formatRow($row, array $filters, User $user): array
    {
        $reportType = $filters['report_type'] ?? 'patient';

        if ($reportType === 'visit') {
            /** @var PatientVisit $row */
            $patient = $row->patient;

            $data = [
                'patient_id'            => $patient?->id,
                'visit_id'              => $row->id,
                'mr_number'             => $patient?->mr_number,
                'patient_name'          => $patient?->patient_name,
                'patient_father_name'   => $patient?->patient_father_name,
                'patient_gender'        => $patient?->patient_gender,
                'patient_gender_label'  => $this->genderLabel($patient?->patient_gender),
                'patient_age'           => $patient?->patient_age,
                'patient_age_unit'      => $patient?->patient_age_unit ?? 'years',
                'patient_age_display'   => $this->ageDisplay($patient?->patient_age, $patient?->patient_age_unit),
                'patient_cell'          => $patient?->patient_cell,
                'patient_cnic'          => $patient?->patient_cnic,
                'patient_address'       => $patient?->patient_address,
                'registration_date'     => $patient?->created_at?->format('Y-m-d'),
                'total_visits'          => null,
                'latest_visit'          => [
                    'id'         => $row->id,
                    'visit_date' => $row->visit_date?->format('Y-m-d'),
                    'visit_time' => $row->visit_time,
                    'status'     => $row->status,
                ],
                'has_prescription'      => $row->prescription !== null,
                'has_laboratory_result' => (bool) ($row->has_laboratory_result_exists ?? false),
                'has_clinical_scan'     => (bool) ($row->has_clinical_scan_exists ?? false),
                'last_visit_doctor'     => $row->doctor?->name,
            ];
        } else {
            /** @var Patient $row */
            $latest = $row->latestVisit;

            $data = [
                'patient_id'            => $row->id,
                'mr_number'             => $row->mr_number,
                'patient_name'          => $row->patient_name,
                'patient_father_name'   => $row->patient_father_name,
                'patient_gender'        => $row->patient_gender,
                'patient_gender_label'  => $this->genderLabel($row->patient_gender),
                'patient_age'           => $row->patient_age,
                'patient_age_unit'      => $row->patient_age_unit ?? 'years',
                'patient_age_display'   => $this->ageDisplay($row->patient_age, $row->patient_age_unit),
                'patient_cell'          => $row->patient_cell,
                'patient_cnic'          => $row->patient_cnic,
                'patient_address'       => $row->patient_address,
                'registration_date'     => $row->created_at?->format('Y-m-d'),
                'total_visits'          => (int) ($row->visits_count ?? 0),
                'latest_visit'          => $latest ? [
                    'id'         => $latest->id,
                    'visit_date' => $latest->visit_date?->format('Y-m-d'),
                    'visit_time' => $latest->visit_time,
                    'status'     => $latest->status,
                ] : null,
                'has_prescription'      => (bool) ($row->has_prescription_exists ?? false),
                'has_laboratory_result' => (bool) ($row->has_laboratory_result_exists ?? false),
                'has_clinical_scan'     => (bool) ($row->has_clinical_scan_exists ?? false),
                'last_visit_doctor'     => $latest?->doctor?->name,
            ];
        }

        return $this->maskSensitiveFields($data, $user);
    }

    protected function maskSensitiveFields(array $data, User $user): array
    {
        if ($user->can('view patients') || $user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return $data;
        }

        if ($user->can('view limited patient info')) {
            if (! empty($data['patient_cnic'])) {
                $data['patient_cnic'] = $this->maskCnic($data['patient_cnic']);
            }

            if (! empty($data['patient_cell'])) {
                $data['patient_cell'] = $this->maskCell($data['patient_cell']);
            }
        }

        return $data;
    }

    protected function maskCnic(?string $cnic): ?string
    {
        if (! $cnic) {
            return $cnic;
        }

        return preg_replace('/^\d{5}-\d{7}-\d$/', '*****-*******-*', $cnic) ?? '*****-*******-*';
    }

    protected function maskCell(?string $cell): ?string
    {
        if (! $cell || strlen($cell) < 4) {
            return $cell;
        }

        return substr($cell, 0, 4).str_repeat('*', max(strlen($cell) - 4, 0));
    }

    protected function genderLabel(?string $gender): ?string
    {
        return match ($gender) {
            'male'   => 'Male',
            'female' => 'Female',
            'other'  => 'Other',
            default  => null,
        };
    }

    protected function ageDisplay(?int $age, ?string $unit): ?string
    {
        if ($age === null) {
            return null;
        }

        $label = match ($unit) {
            'months' => 'Months',
            'days'   => 'Days',
            default  => 'Years',
        };

        return "{$age} {$label}";
    }

    protected function describeFilters(array $filters): array
    {
        $labels = [];

        foreach ([
            'report_type'           => 'Report Type',
            'filter_by'             => 'Filter By',
            'from_date'             => 'From Date',
            'to_date'               => 'To Date',
            'mr_number'             => 'MR Number',
            'patient_name'          => 'Patient Name',
            'patient_father_name'   => 'Father Name',
            'patient_gender'        => 'Gender',
            'age_from'              => 'Age From',
            'age_to'                => 'Age To',
            'age_unit'              => 'Age Unit',
            'patient_cell'          => 'Cell',
            'patient_cnic'          => 'CNIC',
            'status'                => 'Visit Status',
            'doctor_id'             => 'Doctor',
            'has_prescription'      => 'Has Prescription',
            'has_laboratory_result' => 'Has Lab Result',
            'has_clinical_scan'     => 'Has Clinical Scan',
            'search'                => 'Search',
        ] as $key => $label) {
            if (! empty($filters[$key])) {
                $labels[$label] = (string) $filters[$key];
            }
        }

        return $labels;
    }
}
