<?php

namespace App\Services;

use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\User;
use App\Support\ClinicalScanFieldKeyGenerator;
use Illuminate\Support\Facades\DB;

class ClinicalScanTemplateService
{
    public function create(array $data, User $user): ClinicalScanTemplate
    {
        return DB::transaction(function () use ($data, $user) {
            $template = ClinicalScanTemplate::create([
                'template_name' => $data['template_name'],
                'description'   => $data['description'] ?? null,
                'is_active'     => $data['is_active'] ?? true,
                'created_by'    => $user->id,
                'updated_by'    => $user->id,
            ]);

            $this->syncFields($template, $data['fields'], $user);

            return $template->load('fields');
        });
    }

    public function update(ClinicalScanTemplate $template, array $data, User $user): ClinicalScanTemplate
    {
        return DB::transaction(function () use ($template, $data, $user) {
            $template->update([
                'template_name' => $data['template_name'],
                'description'   => $data['description'] ?? null,
                'is_active'     => $data['is_active'] ?? $template->is_active,
                'updated_by'    => $user->id,
            ]);

            $this->syncFields($template, $data['fields'], $user);

            return $template->fresh(['fields']);
        });
    }

    protected function syncFields(ClinicalScanTemplate $template, array $rows, User $user): void
    {
        $keptIds = [];
        $usedKeys = [];

        foreach ($rows as $index => $row) {
            $fieldKey = $row['field_key'] ?? ClinicalScanFieldKeyGenerator::fromLabel($row['field_label'], 'field_'.$index);
            $baseKey = $fieldKey;
            $suffix = 1;

            while (in_array($fieldKey, $usedKeys, true)) {
                $fieldKey = $baseKey.'_'.$suffix;
                $suffix++;
            }

            $usedKeys[] = $fieldKey;

            $payload = [
                'field_label'  => $row['field_label'],
                'field_key'    => $fieldKey,
                'field_type'   => $row['field_type'] ?? 'textarea',
                'options'      => $row['options'] ?? null,
                'default_value'=> $row['default_value'] ?? null,
                'placeholder'  => $row['placeholder'] ?? null,
                'is_required'  => (bool) ($row['is_required'] ?? false),
                'sort_order'   => $row['sort_order'] ?? ($index + 1),
                'updated_by'   => $user->id,
            ];

            if (! empty($row['id'])) {
                $field = ClinicalScanTemplateField::query()
                    ->where('clinical_scan_template_id', $template->id)
                    ->whereKey($row['id'])
                    ->firstOrFail();

                $field->update($payload);
                $keptIds[] = $field->id;

                continue;
            }

            $field = ClinicalScanTemplateField::create([
                ...$payload,
                'clinical_scan_template_id' => $template->id,
                'created_by'                => $user->id,
            ]);
            $keptIds[] = $field->id;
        }

        ClinicalScanTemplateField::query()
            ->where('clinical_scan_template_id', $template->id)
            ->whereNotIn('id', $keptIds)
            ->each(fn (ClinicalScanTemplateField $field) => $field->delete());
    }
}
