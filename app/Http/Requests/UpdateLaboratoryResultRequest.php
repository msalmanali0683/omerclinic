<?php

namespace App\Http\Requests;

use App\Models\LaboratoryResult;
use App\Models\LaboratoryResultAttachment;
use App\Models\LaboratoryResultValue;
use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateLaboratoryResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var LaboratoryResult|null $result */
        $result = $this->route('laboratory_result');

        return $result && $this->user()->can('update', $result);
    }

    public function rules(): array
    {
        return [
            'status'                                   => 'nullable|string|in:draft,completed,verified,cancelled',
            'remarks'                                  => 'nullable|string|max:3000',
            'test_price'                               => 'nullable|numeric|min:0|max:99999999.99',
            'values'                                   => 'required|array',
            'values.*.id'                              => 'nullable|integer|exists:laboratory_result_values,id',
            'values.*.laboratory_test_template_field_id' => 'nullable|integer|exists:laboratory_test_template_fields,id',
            'values.*.field_value'                     => 'nullable|string|max:10000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var LaboratoryResult $result */
            $result = $this->route('laboratory_result');

            $template = $result->laboratory_test_template_id
                ? LaboratoryTestTemplate::with('fields')->find($result->laboratory_test_template_id)
                : null;

            if (! $template) {
                return;
            }

            foreach ($this->input('values', []) as $index => $row) {
                if (! empty($row['id'])) {
                    $belongs = LaboratoryResultValue::query()
                        ->where('laboratory_result_id', $result->id)
                        ->whereKey($row['id'])
                        ->exists();

                    if (! $belongs) {
                        $validator->errors()->add("values.{$index}.id", 'The value row does not belong to this result.');
                    }
                }

                if (! empty($row['laboratory_test_template_field_id'])) {
                    $fieldBelongs = LaboratoryTestTemplateField::query()
                        ->where('laboratory_test_template_id', $template->id)
                        ->whereKey($row['laboratory_test_template_field_id'])
                        ->exists();

                    if (! $fieldBelongs) {
                        $validator->errors()->add("values.{$index}.laboratory_test_template_field_id", 'Invalid template field for this result.');
                    }
                }
            }

            $valuesByFieldId = collect($this->input('values', []))->keyBy('laboratory_test_template_field_id');

            foreach ($template->fields as $field) {
                if (! $field->is_required) {
                    continue;
                }

                $valueRow = $valuesByFieldId->get($field->id);
                $fieldValue = $valueRow['field_value'] ?? null;

                if ($field->field_type === 'image') {
                    if (blank($fieldValue) || ! is_numeric($fieldValue)) {
                        $validator->errors()->add('values', "The {$field->field_label} field is required.");

                        continue;
                    }

                    $attachmentExists = LaboratoryResultAttachment::query()
                        ->where('laboratory_result_id', $result->id)
                        ->whereKey((int) $fieldValue)
                        ->exists();

                    if (! $attachmentExists) {
                        $validator->errors()->add('values', "The {$field->field_label} image is invalid or missing.");
                    }

                    continue;
                }

                if (! $valueRow || blank($fieldValue)) {
                    $validator->errors()->add('values', "The {$field->field_label} field is required.");
                }
            }
        });
    }
}
