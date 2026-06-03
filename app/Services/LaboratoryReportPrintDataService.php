<?php

namespace App\Services;

use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientVisitResource;
use App\Models\LaboratoryResult;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Collection;

class LaboratoryReportPrintDataService
{
    public function getForResult(LaboratoryResult $result, ?User $user = null): array
    {
        $result->loadMissing([
            'patient',
            'visit',
            'values' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return [
            'hospital_name'        => config('hospital.name'),
            'report_footer_lines'  => config('hospital.lab_report_footer'),
            'title'                => 'Laboratory Test Report',
            'generated_at'         => now()->format('Y-m-d H:i'),
            'patient'            => $result->patient
                ? (new PatientResource($result->patient))->resolve()
                : null,
            'visit'              => $result->visit
                ? (new PatientVisitResource($result->visit))->resolve()
                : null,
            'laboratory_results' => [$this->mapLaboratoryResult($result)],
        ];
    }

    public function getForVisit(PatientVisit $visit, ?User $user = null): array
    {
        $user ??= auth()->user();

        $visit->loadMissing('patient');

        $results = $this->fetchPrintableResults($visit);

        return [
            'hospital_name'        => config('hospital.name'),
            'report_footer_lines'  => config('hospital.lab_report_footer'),
            'title'                => 'Laboratory Test Report',
            'generated_at'         => now()->format('Y-m-d H:i'),
            'patient'            => $visit->patient
                ? (new PatientResource($visit->patient))->resolve()
                : null,
            'visit'              => (new PatientVisitResource($visit))->resolve(),
            'laboratory_results' => $results
                ->map(fn (LaboratoryResult $result) => $this->mapLaboratoryResult($result))
                ->values()
                ->all(),
        ];
    }

    protected function fetchPrintableResults(PatientVisit $visit): Collection
    {
        $statuses = [
            LaboratoryResult::STATUS_COMPLETED,
            LaboratoryResult::STATUS_VERIFIED,
        ];

        if ($this->allowsDraftPrinting()) {
            $statuses[] = LaboratoryResult::STATUS_DRAFT;
        }

        return LaboratoryResult::query()
            ->with(['values' => fn ($query) => $query->orderBy('sort_order')])
            ->where('patient_visit_id', $visit->id)
            ->whereIn('status', $statuses)
            ->orderBy('result_date')
            ->orderBy('result_time')
            ->orderBy('id')
            ->get();
    }

    protected function allowsDraftPrinting(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('edit laboratory results') || $user->can('create laboratory results');
    }

    protected function mapLaboratoryResult(LaboratoryResult $result): array
    {
        return [
            'id'          => $result->id,
            'test_name'   => $result->test_name,
            'test_code'   => $result->test_code,
            'test_price'  => $result->test_price,
            'status'      => $result->status,
            'result_date' => $result->result_date?->format('Y-m-d'),
            'result_time' => $result->result_time,
            'remarks'     => $result->remarks,
            'values'      => $result->values->map(fn ($value) => [
                'id'              => $value->id,
                'field_label'     => $value->field_label,
                'field_key'       => $value->field_key,
                'field_type'      => $value->field_type,
                'field_value'     => $value->field_value,
                'unit'            => $value->unit,
                'reference_range' => $value->reference_range,
                'sort_order'      => $value->sort_order,
            ])->values()->all(),
        ];
    }
}
