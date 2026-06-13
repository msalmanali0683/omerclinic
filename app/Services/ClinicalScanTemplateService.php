<?php

namespace App\Services;

use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\User;
use App\Support\ClinicalScanFieldKeyGenerator;
use App\Support\SoftDeletedTemplateFieldKeyRelease;
use Illuminate\Support\Facades\DB;

class ClinicalScanTemplateService
{
    public function create(array $data, User $user): ClinicalScanTemplate
    {
        return DB::transaction(function () use ($data, $user) {
            $existing = ClinicalScanTemplate::withTrashed()
                ->where('template_name', $data['template_name'])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update([
                    'description' => $data['description'] ?? null,
                    'is_active'   => $data['is_active'] ?? true,
                    'updated_by'  => $user->id,
                ]);

                $this->syncFields($existing, $data['fields'], $user);

                return $existing->load('fields');
            }

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
            $keySource = ! empty($row['group_label'])
                ? trim($row['group_label'].' '.($row['field_label'] ?? ''))
                : ($row['field_label'] ?? '');

            $fieldKey = $row['field_key'] ?? ClinicalScanFieldKeyGenerator::fromLabel($keySource, 'field_'.$index);
            $baseKey = $fieldKey;
            $suffix = 1;

            while (in_array($fieldKey, $usedKeys, true)) {
                $fieldKey = $baseKey.'_'.$suffix;
                $suffix++;
            }

            $usedKeys[] = $fieldKey;

            $defaults = $this->normalizeDefaultValues($row);

            $payload = [
                'field_label'   => $row['field_label'],
                'group_label'   => $row['group_label'] ?? null,
                'field_key'     => $fieldKey,
                'field_type'    => $row['field_type'] ?? 'textarea',
                'options'       => $row['options'] ?? null,
                'default_value' => $defaults['default_value'],
                'default_values'=> $defaults['default_values'],
                'placeholder'   => $row['placeholder'] ?? null,
                'is_required'   => (bool) ($row['is_required'] ?? false),
                'print_in_box'  => (bool) ($row['print_in_box'] ?? false),
                'sort_order'    => $row['sort_order'] ?? ($index + 1),
                'updated_by'    => $user->id,
            ];

            if (! empty($row['id'])) {
                $fieldId = (int) $row['id'];

                SoftDeletedTemplateFieldKeyRelease::release(
                    ClinicalScanTemplateField::class,
                    'clinical_scan_template_id',
                    $template->id,
                    $fieldKey,
                    $fieldId,
                );

                $field = ClinicalScanTemplateField::withTrashed()
                    ->where('clinical_scan_template_id', $template->id)
                    ->whereKey($fieldId)
                    ->firstOrFail();

                if ($field->trashed()) {
                    $field->restore();
                }

                $field->update($payload);
                $keptIds[] = $field->id;

                continue;
            }

            $existingField = ClinicalScanTemplateField::withTrashed()
                ->where('clinical_scan_template_id', $template->id)
                ->where('field_key', $fieldKey)
                ->first();

            if ($existingField) {
                SoftDeletedTemplateFieldKeyRelease::release(
                    ClinicalScanTemplateField::class,
                    'clinical_scan_template_id',
                    $template->id,
                    $fieldKey,
                    $existingField->id,
                );

                if ($existingField->trashed()) {
                    $existingField->restore();
                }

                $existingField->update($payload);
                $keptIds[] = $existingField->id;

                continue;
            }

            SoftDeletedTemplateFieldKeyRelease::release(
                ClinicalScanTemplateField::class,
                'clinical_scan_template_id',
                $template->id,
                $fieldKey,
            );

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

    /**
     * @param  array<string, mixed>  $row
     * @return array{default_value: ?string, default_values: ?array<int, string>}
     */
    protected function normalizeDefaultValues(array $row): array
    {
        $values = [];

        if (! empty($row['default_values']) && is_array($row['default_values'])) {
            foreach ($row['default_values'] as $value) {
                $trimmed = trim((string) $value);

                if ($trimmed !== '') {
                    $values[] = $trimmed;
                }
            }
        }

        if ($values === [] && ! empty($row['default_value'])) {
            $trimmed = trim((string) $row['default_value']);

            if ($trimmed !== '') {
                $values[] = $trimmed;
            }
        }

        if ($values === []) {
            return [
                'default_value'  => null,
                'default_values' => null,
            ];
        }

        return [
            'default_value'  => $values[0],
            'default_values' => $values,
        ];
    }
}
