<?php

namespace Database\Seeders;

use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Support\LaboratoryFieldKeyGenerator;
use Illuminate\Database\Seeder;

class LaboratorySeeder extends Seeder
{
    public function run(): void
    {
        $templates = require database_path('seeders/data/laboratory_templates.php');

        foreach ($templates as $templateData) {
            $template = LaboratoryTestTemplate::updateOrCreate(
                ['test_name' => $templateData['test_name']],
                [
                    'test_code' => $templateData['test_code'] ?? null,
                    'description' => $templateData['description'] ?? null,
                    'is_active' => true,
                ]
            );

            foreach ($templateData['fields'] ?? [] as $index => $field) {
                $fieldLabel = $field['field_label'] ?? '';
                $fieldKey = LaboratoryFieldKeyGenerator::fromLabel($fieldLabel, 'field_' . ($index + 1));

                LaboratoryTestTemplateField::updateOrCreate(
                    [
                        'laboratory_test_template_id' => $template->id,
                        'field_key' => $fieldKey,
                    ],
                    [
                        'field_label' => $fieldLabel,
                        'field_type' => $field['field_type'] ?? 'text',
                        'unit' => $field['unit'] ?? null,
                        'reference_range' => $field['reference_range'] ?? null,
                        'options' => $field['options'] ?? [],
                        'sort_order' => $field['sort_order'] ?? ($index + 1),
                    ]
                );
            }
        }
    }
}
