<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Patient::class);
    }

    public function rules(): array
    {
        $user = $this->user();
        $requiresDoctor = $user?->can('add patient to queue') && ! $user->hasRole('doctor');

        return [
            'patient_name'        => 'required|string|max:255',
            'patient_father_name' => 'nullable|string|max:255',
            'patient_gender'      => 'required|string|in:male,female,other',
            'patient_age'         => 'required|integer|min:0|max:150',
            'patient_age_unit'    => 'required|string|in:years,months,days',
            'patient_cell'        => 'required|string|max:30',
            'patient_address'     => 'nullable|string|max:1000',
            'patient_cnic'        => 'nullable|string|max:20',
            'add_to_queue'        => 'nullable|boolean',
            'doctor_id'           => [
                Rule::requiredIf($requiresDoctor),
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $doctor = User::find($value);
                    if (! $doctor?->hasRole('doctor')) {
                        $fail('The selected user must have the doctor role.');
                    }
                },
            ],
            'reason_for_visit'    => 'nullable|string|max:1000',
            'notes'               => 'nullable|string|max:1000',
            'force_create'        => 'nullable|boolean',
        ];
    }
}
