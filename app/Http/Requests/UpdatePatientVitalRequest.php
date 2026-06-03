<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientVitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vital'));
    }

    public function rules(): array
    {
        return [
            'blood_pressure'    => 'nullable|string|max:30',
            'temperature'       => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'pulse_rate'        => 'nullable|integer',
            'respiratory_rate'  => 'nullable|integer',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
