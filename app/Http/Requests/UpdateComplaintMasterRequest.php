<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('complaint_master'));
    }

    public function rules(): array
    {
        return [
            'complaint_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('complaint_masters', 'complaint_name')->ignore($this->route('complaint_master')),
            ],
        ];
    }
}
