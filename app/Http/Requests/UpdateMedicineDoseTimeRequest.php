<?php

namespace App\Http\Requests;

use App\Support\ActiveRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

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
                ActiveRecordValidation::unique('medicine_dose_times', 'dose_time', $this->route('medicineDoseTime')),
            ],
        ];
    }
}
