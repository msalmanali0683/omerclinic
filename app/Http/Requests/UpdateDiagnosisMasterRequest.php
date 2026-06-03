<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiagnosisMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('diagnosis_master'));
    }

    public function rules(): array
    {
        return [
            'diagnosis_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('diagnosis_masters', 'diagnosis_name')->ignore($this->route('diagnosis_master')),
            ],
        ];
    }
}
