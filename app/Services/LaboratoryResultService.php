<?php

namespace App\Services;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultValue;
use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LaboratoryResultService
{
    public function create(array $data, User $user): LaboratoryResult
    {
        return DB::transaction(function () use ($data, $user) {
            $template = LaboratoryTestTemplate::with('fields')->findOrFail($data['laboratory_test_template_id']);

            $testPrice = array_key_exists('test_price', $data)
                ? $data['test_price']
                : $template->test_price;

            $visitId = ! empty($data['patient_visit_id']) ? (int) $data['patient_visit_id'] : null;

            $result = LaboratoryResult::create([
                'patient_id'                  => $data['patient_id'],
                'patient_visit_id'            => $visitId,
                'laboratory_test_template_id' => $template->id,
                'test_name'                   => $template->test_name,
                'test_code'                   => $template->test_code,
                'test_price'                  => $testPrice,
                'lab_operator_id'             => $user->id,
                'result_date'                 => $data['result_date'] ?? now()->toDateString(),
                'result_time'                 => $data['result_time'] ?? now()->format('H:i:s'),
                'status'                      => $data['status'] ?? LaboratoryResult::STATUS_COMPLETED,
                'remarks'                     => $data['remarks'] ?? null,
                'created_by'                  => $user->id,
                'updated_by'                  => $user->id,
            ]);

            if (! empty($data['values'])) {
                $this->syncValues($result, $template, $data['values'], $user);
            }

            return $result->load(['values', 'patient', 'visit.doctor', 'template', 'labOperator', 'bill']);
        });
    }

    public function update(LaboratoryResult $result, array $data, User $user): LaboratoryResult
    {
        return DB::transaction(function () use ($result, $data, $user) {
            $template = $result->laboratory_test_template_id
                ? LaboratoryTestTemplate::with('fields')->find($result->laboratory_test_template_id)
                : null;

            $result->update([
                'status'     => $data['status'] ?? $result->status,
                'remarks'    => $data['remarks'] ?? null,
                'test_price' => array_key_exists('test_price', $data) ? $data['test_price'] : $result->test_price,
                'updated_by' => $user->id,
            ]);

            if ($template) {
                $this->syncValues($result, $template, $data['values'], $user);
            }

            return $result->fresh(['values', 'patient', 'visit.doctor', 'template', 'labOperator']);
        });
    }

    public function verify(LaboratoryResult $result, User $user): LaboratoryResult
    {
        $result->update([
            'status'     => LaboratoryResult::STATUS_VERIFIED,
            'updated_by' => $user->id,
        ]);

        return $result->fresh(['values', 'patient', 'visit.doctor', 'template', 'labOperator']);
    }

    public function getPrintData(LaboratoryResult $result, ?User $user = null): array
    {
        return app(LaboratoryReportPrintDataService::class)->getForResult($result, $user);
    }

    public function getVisitPrintData(PatientVisit $visit, ?User $user = null): array
    {
        return app(LaboratoryReportPrintDataService::class)->getForVisit($visit, $user);
    }

    protected function syncValues(LaboratoryResult $result, LaboratoryTestTemplate $template, array $rows, User $user): void
    {
        $templateFields = $template->fields->keyBy('id');
        $keptIds = [];

        foreach ($rows as $row) {
            $templateFieldId = (int) $row['laboratory_test_template_field_id'];
            $templateField = $templateFields->get($templateFieldId);

            if (! $templateField) {
                continue;
            }

            $payload = [
                'laboratory_test_template_field_id' => $templateField->id,
                'field_label'                       => $templateField->field_label,
                'field_key'                         => $templateField->field_key,
                'field_type'                        => $templateField->field_type,
                'field_value'                       => $row['field_value'] ?? null,
                'unit'                              => $templateField->unit,
                'reference_range'                   => $templateField->reference_range,
                'sort_order'                        => $templateField->sort_order,
                'updated_by'                        => $user->id,
            ];

            if (! empty($row['id'])) {
                $value = LaboratoryResultValue::query()
                    ->where('laboratory_result_id', $result->id)
                    ->whereKey($row['id'])
                    ->firstOrFail();

                $value->update($payload);
                $keptIds[] = $value->id;

                continue;
            }

            $value = LaboratoryResultValue::create([
                ...$payload,
                'laboratory_result_id' => $result->id,
                'created_by'           => $user->id,
            ]);
            $keptIds[] = $value->id;
        }

        LaboratoryResultValue::query()
            ->where('laboratory_result_id', $result->id)
            ->whereNotIn('id', $keptIds)
            ->each(fn (LaboratoryResultValue $value) => $value->delete());
    }
}
