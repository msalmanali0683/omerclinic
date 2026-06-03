<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMedicineDoseFromMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('medicineDoseFromMeal'));
    }

    public function rules(): array
    {
        return [
            'dose_from_meal' => [
                'required',
                'string',
                'max:255',
                Rule::unique('medicine_dose_from_meals', 'dose_from_meal')->ignore($this->route('medicineDoseFromMeal')),
            ],
        ];
    }
}
