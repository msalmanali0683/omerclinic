<?php

namespace App\Http\Requests;

use App\Models\ClinicalScan;
use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\ClinicalScanValue;
use App\Models\PatientVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateClinicalScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalScan|null $scan */
        $scan = $this->route('clinical_scan');

        return $scan && $this->user()->can('update', $scan);
    }

    public function rules(): array
    {
        return [
            'status'                                  => 'nullable|string|in:draft,completed,cancelled',
            'notes'                                   => 'nullable|string|max:3000',
            'impression'                              => 'nullable|string|max:3000',
            'values'                                  => 'required|array',
            'values.*.id'                             => 'nullable|integer|exists:clinical_scan_values,id',
            'values.*.clinical_scan_template_field_id'  => 'nullable|integer|exists:clinical_scan_template_fields,id',
            'values.*.field_value'                    => 'nullable|string|max:10000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var ClinicalScan $scan */
            $scan = $this->route('clinical_scan');
            $visit = $scan->visit ?? PatientVisit::find($scan->patient_visit_id);

            if ($visit && $visit->status === PatientVisit::STATUS_CANCELLED) {
                if (! $this->user()->hasAnyRole(['super-admin', 'hospital-admin'])) {
                    $validator->errors()->add('values', 'Cannot update scan for a cancelled visit.');
                }
            }

            $template = $scan->clinical_scan_template_id
                ? ClinicalScanTemplate::with('fields')->find($scan->clinical_scan_template_id)
                : null;

            if (! $template) {
                return;
            }

            foreach ($this->input('values', []) as $index => $row) {
                if (! empty($row['id'])) {
                    $belongs = ClinicalScanValue::query()
                        ->where('clinical_scan_id', $scan->id)
                        ->whereKey($row['id'])
                        ->exists();

                    if (! $belongs) {
                        $validator->errors()->add("values.{$index}.id", 'The value row does not belong to this scan.');
                    }
                }

                if (! empty($row['clinical_scan_template_field_id'])) {
                    $fieldBelongs = ClinicalScanTemplateField::query()
                        ->where('clinical_scan_template_id', $template->id)
                        ->whereKey($row['clinical_scan_template_field_id'])
                        ->exists();

                    if (! $fieldBelongs) {
                        $validator->errors()->add("values.{$index}.clinical_scan_template_field_id", 'Invalid template field for this scan.');
                    }
                }
            }

            $valuesByFieldId = collect($this->input('values', []))->keyBy('clinical_scan_template_field_id');

            foreach ($template->fields as $field) {
                if (! $field->is_required) {
                    continue;
                }

                $valueRow = $valuesByFieldId->get($field->id);

                if (! $valueRow || blank($valueRow['field_value'] ?? null)) {
                    $validator->errors()->add('values', "The {$field->field_label} field is required.");
                }
            }
        });
    }
}
