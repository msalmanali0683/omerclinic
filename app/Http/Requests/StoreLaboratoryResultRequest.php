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
            'patient_visit_id'                         => 'required|integer|exists:patient_visits,id',
            'laboratory_test_template_id'              => 'required|integer|exists:laboratory_test_templates,id',
            'result_date'                              => 'nullable|date',
            'result_time'                              => 'nullable',
            'status'                                   => 'nullable|string|in:draft,completed,verified,cancelled',
            'remarks'                                  => 'nullable|string|max:3000',
            'values'                                   => 'required|array',
            'values.*.laboratory_test_template_field_id' => 'required|integer|exists:laboratory_test_template_fields,id',
            'values.*.field_value'                     => 'nullable|string|max:10000',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $visit = PatientVisit::find($this->patient_visit_id);

            if ($visit && (int) $visit->patient_id !== (int) $this->patient_id) {
                $validator->errors()->add('patient_visit_id', 'The visit does not belong to this patient.');
            }

            if ($visit && $visit->status === PatientVisit::STATUS_CANCELLED) {
                if (! $this->user()->hasAnyRole(['super-admin', 'hospital-admin'])) {
                    $validator->errors()->add('patient_visit_id', 'Cannot create laboratory result for a cancelled visit.');
                }
            }

            $template = LaboratoryTestTemplate::with('fields')->find($this->laboratory_test_template_id);

            if (! $template) {
                return;
            }

            if (! $template->is_active) {
                $validator->errors()->add('laboratory_test_template_id', 'The selected test template is not active.');
            }

            $valuesByFieldId = collect($this->input('values', []))->keyBy('laboratory_test_template_field_id');

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
