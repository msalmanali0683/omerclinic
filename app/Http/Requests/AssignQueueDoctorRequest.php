<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AssignQueueDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignDoctor', $this->route('visit'));
    }

    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);
                    if (! $user?->hasRole('doctor')) {
                        $fail('The selected user must have the doctor role.');
                    }
                },
            ],
        ];
    }
}
