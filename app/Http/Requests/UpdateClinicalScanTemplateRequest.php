<?php

namespace App\Http\Requests;

use App\Models\ClinicalScanTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalScanTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalScanTemplate|null $template */
        $template = $this->route('clinical_scan_template');

        return $template && $this->user()->can('update', $template);
    }

    public function rules(): array
    {
        /** @var ClinicalScanTemplate|null $template */
        $template = $this->route('clinical_scan_template');

        return [
            'template_name'           => [
                'required',
                'string',
                'max:255',
                Rule::unique('clinical_scan_templates', 'template_name')->ignore($template?->id),
            ],
            'description'             => 'nullable|string|max:2000',
            'is_active'               => 'boolean',
            'fields'                  => 'required|array|min:1',
            'fields.*.id'             => 'nullable|integer|exists:clinical_scan_template_fields,id',
            'fields.*.field_label'     => 'required|string|max:255',
            'fields.*.group_label'    => 'nullable|string|max:255',
            'fields.*.field_type'     => 'required|string|in:text,textarea,number,select,checkbox,date',
            'fields.*.options'         => 'nullable|array',
            'fields.*.default_value'   => 'nullable|string|max:2000',
            'fields.*.default_values'  => 'nullable|array',
            'fields.*.default_values.*'=> 'nullable|string|max:2000',
            'fields.*.placeholder'     => 'nullable|string|max:255',
            'fields.*.is_required'    => 'boolean',
            'fields.*.print_in_box'   => 'boolean',
            'fields.*.sort_order'     => 'nullable|integer|min:0',
        ];
    }
}
