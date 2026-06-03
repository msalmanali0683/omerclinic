<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPatientToQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addToQueue', \App\Models\PatientVisit::class);
    }

    public function rules(): array
    {
        return [
            'doctor_id'        => [
                Rule::requiredIf(fn () => ! $this->user()->hasRole('doctor')),
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $user = User::find($value);
                    if (! $user?->hasRole('doctor')) {
                        $fail('The selected user must have the doctor role.');
                    }
                },
            ],
            'reason_for_visit' => 'nullable|string|max:1000',
            'notes'            => 'nullable|string|max:1000',
            'visit_date'       => 'nullable|date',
        ];
    }
}
