<?php

namespace App\Services;

use App\Models\ClinicalScan;
use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\ClinicalScanValue;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClinicalScanService
{
    public function create(array $data, User $user): ClinicalScan
    {
        return DB::transaction(function () use ($data, $user) {
            $template = ClinicalScanTemplate::with('fields')->findOrFail($data['clinical_scan_template_id']);
            $visit = PatientVisit::findOrFail($data['patient_visit_id']);

            $scan = ClinicalScan::create([
                'patient_id'                => $data['patient_id'],
                'patient_visit_id'          => $data['patient_visit_id'],
                'clinical_scan_template_id' => $template->id,
                'scan_template_name'        => $template->template_name,
                'scan_operator_id'          => $user->id,
                'scan_date'                 => $data['scan_date'] ?? now()->toDateString(),
                'scan_time'                 => $data['scan_time'] ?? now()->format('H:i:s'),
                'status'                    => $data['status'] ?? ClinicalScan::STATUS_COMPLETED,
                'notes'                     => $data['notes'] ?? null,
                'impression'                => $data['impression'] ?? null,
                'created_by'                => $user->id,
                'updated_by'                => $user->id,
            ]);

            $this->syncValues($scan, $template, $data['values'], $user);

            return $scan->load(['values', 'patient', 'visit.doctor', 'template', 'scanOperator']);
        });
    }

    public function update(ClinicalScan $scan, array $data, User $user): ClinicalScan
    {
        return DB::transaction(function () use ($scan, $data, $user) {
            $template = $scan->clinical_scan_template_id
                ? ClinicalScanTemplate::with('fields')->find($scan->clinical_scan_template_id)
                : null;

            $scan->update([
                'status'     => $data['status'] ?? $scan->status,
                'notes'      => $data['notes'] ?? null,
                'impression' => $data['impression'] ?? null,
                'updated_by' => $user->id,
            ]);

            if ($template) {
                $this->syncValues($scan, $template, $data['values'], $user);
            }

            return $scan->fresh(['values', 'patient', 'visit.doctor', 'template', 'scanOperator']);
        });
    }

    public function getPrintData(ClinicalScan $scan, ?User $user = null): array
    {
        return app(VisitPrintDataService::class)->getForClinicalScan($scan, $user);
    }

    protected function syncValues(ClinicalScan $scan, ClinicalScanTemplate $template, array $rows, User $user): void
    {
        $templateFields = $template->fields->keyBy('id');
        $keptIds = [];

        foreach ($rows as $row) {
            $templateFieldId = (int) $row['clinical_scan_template_field_id'];
            $templateField = $templateFields->get($templateFieldId);

            if (! $templateField) {
                continue;
            }

            $payload = [
                'clinical_scan_template_field_id' => $templateField->id,
                'field_label'                     => $templateField->field_label,
                'field_key'                       => $templateField->field_key,
                'field_type'                      => $templateField->field_type,
                'field_value'                     => $row['field_value'] ?? null,
                'sort_order'                      => $templateField->sort_order,
                'updated_by'                      => $user->id,
            ];

            if (! empty($row['id'])) {
                $value = ClinicalScanValue::query()
                    ->where('clinical_scan_id', $scan->id)
                    ->whereKey($row['id'])
                    ->firstOrFail();

                $value->update($payload);
                $keptIds[] = $value->id;

                continue;
            }

            $value = ClinicalScanValue::create([
                ...$payload,
                'clinical_scan_id' => $scan->id,
                'created_by'       => $user->id,
            ]);
            $keptIds[] = $value->id;
        }

        ClinicalScanValue::query()
            ->where('clinical_scan_id', $scan->id)
            ->whereNotIn('id', $keptIds)
            ->each(fn (ClinicalScanValue $value) => $value->delete());
    }
}
