<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindOrCreateMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('select medicines in prescription')
            || $this->user()->can('create medicines');
    }

    public function rules(): array
    {
        return [
            'mdcn_type'              => 'required|string|max:100',
            'mdcn_name'              => 'required|string|max:255',
            'mdcn_size'              => 'nullable|string|max:100',
            'mdcn_time_id'           => 'nullable|integer|exists:medicine_dose_times,id',
            'mdcn_dose_from_meal_id' => 'nullable|integer|exists:medicine_dose_from_meals,id',
        ];
    }
}
