<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicLaboratoryReportVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mr_number'    => 'required|string|max:50',
            'patient_cell' => 'required_without:patient_cnic|nullable|string|max:30',
            'patient_cnic' => 'required_without:patient_cell|nullable|string|max:30',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_cell.required_without' => 'Enter your cell phone number or CNIC.',
            'patient_cnic.required_without' => 'Enter your CNIC or cell phone number.',
        ];
    }
}
