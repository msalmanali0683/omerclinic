<?php

namespace App\Http\Requests;

use App\Models\ClinicalScanTemplate;
use App\Models\ClinicalScanTemplateField;
use App\Models\PatientVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreClinicalScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ClinicalScan::class);
    }

    public function rules(): array
    {
        return [
            'patient_id'                              => 'required|integer|exists:patients,id',
            'patient_visit_id'                        => 'required|integer|exists:patient_visits,id',
            'clinical_scan_template_id'               => 'required|integer|exists:clinical_scan_templates,id',
            'scan_date'                               => 'nullable|date',
            'scan_time'                               => 'nullable',
            'status'                                  => 'nullable|string|in:draft,completed,cancelled',
            'scan_name'                                 => 'nullable|string|max:255',
            'notes'                                   => 'nullable|string|max:3000',
            'impression'                              => 'nullable|string|max:3000',
            'values'                                  => 'required|array',
            'values.*.clinical_scan_template_field_id'=> 'required|integer|exists:clinical_scan_template_fields,id',
            'values.*.field_value'                    => 'nullable|string|max:10000',
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
                    $validator->errors()->add('patient_visit_id', 'Cannot create scan for a cancelled visit.');
                }
            }

            $template = ClinicalScanTemplate::with('fields')->find($this->clinical_scan_template_id);

            if (! $template) {
                return;
            }

            if (! $template->is_active) {
                $validator->errors()->add('clinical_scan_template_id', 'The selected scan template is not active.');
            }

            $valuesByFieldId = collect($this->input('values', []))->keyBy('clinical_scan_template_field_id');

            foreach ($template->fields as $field) {
                if (! $valuesByFieldId->has($field->id)) {
                    if ($field->is_required) {
                        $validator->errors()->add('values', "The {$field->field_label} field is required.");
                    }

                    continue;
                }

                $valueRow = $valuesByFieldId->get($field->id);
                $fieldBelongs = ClinicalScanTemplateField::query()
                    ->where('clinical_scan_template_id', $template->id)
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
