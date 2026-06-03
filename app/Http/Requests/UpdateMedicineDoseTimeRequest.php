<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineDoseTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('medicineDoseTime'));
    }

    public function rules(): array
    {
        return [
            'dose_time' => [
                'required',
                'string',
                'max:255',
                Rule::unique('medicine_dose_times', 'dose_time')->ignore($this->route('medicineDoseTime')),
            ],
        ];
    }
}
