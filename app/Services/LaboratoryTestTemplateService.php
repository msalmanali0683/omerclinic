<?php

namespace App\Services;

use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Models\User;
use App\Support\LaboratoryFieldKeyGenerator;
use App\Support\SoftDeletedTemplateFieldKeyRelease;
use Illuminate\Support\Facades\DB;

class LaboratoryTestTemplateService
{
    public function create(array $data, User $user): LaboratoryTestTemplate
    {
        return DB::transaction(function () use ($data, $user) {
            $existing = LaboratoryTestTemplate::withTrashed()
                ->where('test_name', $data['test_name'])
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->update([
                    'test_code'   => $data['test_code'] ?? null,
                    'test_type'   => $data['test_type'] ?? LaboratoryTestTemplate::TYPE_STANDARD,
                    'test_price'  => $data['test_price'] ?? 0,
                    'description' => $data['description'] ?? null,
                    'is_active'   => $data['is_active'] ?? true,
                    'updated_by'  => $user->id,
                ]);

                $this->syncFields($existing, $data['fields'], $user);

                return $existing->load('fields');
            }

            $template = LaboratoryTestTemplate::create([
                'test_name'   => $data['test_name'],
                'test_code'   => $data['test_code'] ?? null,
                'test_type'   => $data['test_type'] ?? LaboratoryTestTemplate::TYPE_STANDARD,
                'test_price'  => $data['test_price'] ?? 0,
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
                'created_by'  => $user->id,
                'updated_by'  => $user->id,
            ]);

            $this->syncFields($template, $data['fields'], $user);

            return $template->load('fields');
        });
    }

    public function update(LaboratoryTestTemplate $template, array $data, User $user): LaboratoryTestTemplate
    {
        return DB::transaction(function () use ($template, $data, $user) {
            $template->update([
                'test_name'   => $data['test_name'],
                'test_code'   => $data['test_code'] ?? null,
                'test_type'   => $data['test_type'] ?? $template->test_type ?? LaboratoryTestTemplate::TYPE_STANDARD,
                'test_price'  => $data['test_price'] ?? $template->test_price ?? 0,
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? $template->is_active,
                'updated_by'  => $user->id,
            ]);

            $this->syncFields($template, $data['fields'], $user);

            return $template->fresh(['fields']);
        });
    }

    protected function syncFields(LaboratoryTestTemplate $template, array $rows, User $user): void
    {
        $keptIds = [];
        $usedKeys = [];

        foreach ($rows as $index => $row) {
            $fieldKey = $row['field_key'] ?? LaboratoryFieldKeyGenerator::fromLabel($row['field_label'], 'field_'.$index);
            $baseKey = $fieldKey;
            $suffix = 1;

            while (in_array($fieldKey, $usedKeys, true)) {
                $fieldKey = $baseKey.'_'.$suffix;
                $suffix++;
            }

            $usedKeys[] = $fieldKey;

            $payload = [
                'field_label'     => $row['field_label'],
                'field_key'       => $fieldKey,
                'field_type'      => $row['field_type'] ?? 'text',
                'unit'            => $row['unit'] ?? null,
                'reference_range' => $row['reference_range'] ?? null,
                'options'         => $row['options'] ?? null,
                'default_value'   => $row['default_value'] ?? null,
                'placeholder'     => $row['placeholder'] ?? null,
                'is_required'     => (bool) ($row['is_required'] ?? false),
                'sort_order'      => $row['sort_order'] ?? ($index + 1),
                'updated_by'      => $user->id,
            ];

            if (! empty($row['id'])) {
                $fieldId = (int) $row['id'];

                SoftDeletedTemplateFieldKeyRelease::release(
                    LaboratoryTestTemplateField::class,
                    'laboratory_test_template_id',
                    $template->id,
                    $fieldKey,
                    $fieldId,
                );

                $field = LaboratoryTestTemplateField::withTrashed()
                    ->where('laboratory_test_template_id', $template->id)
                    ->whereKey($fieldId)
                    ->firstOrFail();

                if ($field->trashed()) {
                    $field->restore();
                }

                $field->update($payload);
                $keptIds[] = $field->id;

                continue;
            }

            $existingField = LaboratoryTestTemplateField::withTrashed()
                ->where('laboratory_test_template_id', $template->id)
                ->where('field_key', $fieldKey)
                ->first();

            if ($existingField) {
                SoftDeletedTemplateFieldKeyRelease::release(
                    LaboratoryTestTemplateField::class,
                    'laboratory_test_template_id',
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
                LaboratoryTestTemplateField::class,
                'laboratory_test_template_id',
                $template->id,
                $fieldKey,
            );

            $field = LaboratoryTestTemplateField::create([
                ...$payload,
                'laboratory_test_template_id' => $template->id,
                'created_by'                  => $user->id,
            ]);
            $keptIds[] = $field->id;
        }

        LaboratoryTestTemplateField::query()
            ->where('laboratory_test_template_id', $template->id)
            ->whereNotIn('id', $keptIds)
            ->each(fn (LaboratoryTestTemplateField $field) => $field->delete());
    }
}
