<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryTestTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\LaboratoryTestTemplate::class);
    }

    public function rules(): array
    {
        return [
            'test_name'                => 'required|string|max:255|unique:laboratory_test_templates,test_name',
            'test_code'                => 'nullable|string|max:100|unique:laboratory_test_templates,test_code',
            'test_price'               => 'nullable|numeric|min:0|max:99999999.99',
            'description'              => 'nullable|string|max:2000',
            'is_active'                => 'boolean',
            'fields'                   => 'required|array|min:1',
            'fields.*.field_label'     => 'required|string|max:255',
            'fields.*.field_type'      => 'required|string|in:text,textarea,number,select,checkbox,date',
            'fields.*.unit'            => 'nullable|string|max:100',
            'fields.*.reference_range' => 'nullable|string|max:255',
            'fields.*.options'         => 'nullable|array',
            'fields.*.default_value'   => 'nullable|string|max:2000',
            'fields.*.placeholder'     => 'nullable|string|max:255',
            'fields.*.is_required'     => 'boolean',
            'fields.*.sort_order'      => 'nullable|integer|min:0',
        ];
    }
}
