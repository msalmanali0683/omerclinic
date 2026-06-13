<?php

namespace App\Http\Requests;

use App\Models\ClinicalScanTemplateField;
use App\Support\ActiveRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreClinicalScanTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ClinicalScanTemplate::class);
    }

    public function rules(): array
    {
        return [
            'template_name'           => ['required', 'string', 'max:255', ActiveRecordValidation::unique('clinical_scan_templates', 'template_name')],
            'description'             => 'nullable|string|max:2000',
            'is_active'               => 'boolean',
            'fields'                  => 'required|array|min:1',
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
