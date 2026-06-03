<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient'));
    }

    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'patient_name'        => 'required|string|max:255',
            'patient_father_name' => 'nullable|string|max:255',
            'patient_gender'      => 'required|string|in:male,female,other',
            'patient_age'         => 'required|integer|min:0|max:150',
            'patient_age_unit'    => 'required|string|in:years,months,days',
            'patient_cell'        => 'required|string|max:30',
            'patient_address'     => 'nullable|string|max:1000',
            'patient_cnic'        => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('patients', 'patient_cnic')->ignore($patientId),
            ],
        ];
    }
}
