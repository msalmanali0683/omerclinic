<?php

namespace App\Http\Requests;

use App\Support\ActiveRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

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
                ActiveRecordValidation::unique('complaint_masters', 'complaint_name', $this->route('complaint_master')),
            ],
        ];
    }
}
