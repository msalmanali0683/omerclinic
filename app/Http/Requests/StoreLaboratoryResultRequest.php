<?php

namespace App\Http\Requests;

use App\Models\LaboratoryTestTemplate;
use App\Models\LaboratoryTestTemplateField;
use App\Models\PatientVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLaboratoryResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\LaboratoryResult::class);
    }

    public function rules(): array
    {
        return [
            'patient_id'                               => 'required|integer|exists:patients,id',
            'patient_visit_id'                         => 'nullable|integer|exists:patient_visits,id',
            'values'                                   => 'nullable|array',
            'laboratory_test_template_id'              => 'required|integer|exists:laboratory_test_templates,id',
            'result_date'                              => 'nullable|date',
            'result_time'                              => 'nullable',
            'status'                                   => 'nullable|string|in:draft,completed,verified,cancelled',
            'remarks'                                  => 'nullable|string|max:3000',
            'test_price'                               => 'nullable|numeric|min:0|max:99999999.99',
            'values.*.laboratory_test_template_field_id' => 'required_with:values|integer|exists:laboratory_test_template_fields,id',
            'values.*.field_value'                     => 'nullable|string|max:10000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $visit = $this->filled('patient_visit_id')
                ? PatientVisit::find($this->patient_visit_id)
                : null;

            if ($this->filled('patient_visit_id') && ! $visit) {
                $validator->errors()->add('patient_visit_id', 'The selected visit is invalid.');
            }

            if ($visit && (int) $visit->patient_id !== (int) $this->patient_id) {
                $validator->errors()->add('patient_visit_id', 'The visit does not belong to this patient.');
            }

            $template = LaboratoryTestTemplate::with('fields')->find($this->laboratory_test_template_id);

            if (! $template) {
                return;
            }

            if (! $template->is_active) {
                $validator->errors()->add('laboratory_test_template_id', 'The selected test template is not active.');
            }

            $status = $this->input('status', 'completed');
            $values = $this->input('values', []);

            if ($status !== 'draft' && empty($values)) {
                $validator->errors()->add('values', 'Result values are required unless status is draft.');

                return;
            }

            if ($status === 'draft' && empty($values)) {
                return;
            }

            $valuesByFieldId = collect($values)->keyBy('laboratory_test_template_field_id');

            foreach ($template->fields as $field) {
                if (! $valuesByFieldId->has($field->id)) {
                    if ($field->is_required) {
                        $validator->errors()->add('values', "The {$field->field_label} field is required.");
                    }

                    continue;
                }

                $valueRow = $valuesByFieldId->get($field->id);
                $fieldBelongs = LaboratoryTestTemplateField::query()
                    ->where('laboratory_test_template_id', $template->id)
                    ->whereKey($field->id)
                    ->exists();

                if (! $fieldBelongs) {
                    $validator->errors()->add('values', 'One or more fields do not belong to the selected template.');
                    break;
                }

                if ($field->is_required && blank($valueRow['field_value'] ?? null)) {
                    $validator->errors()->add('values', "The {$field->field_label} field is required.");
                }
            }
        });
    }
}
