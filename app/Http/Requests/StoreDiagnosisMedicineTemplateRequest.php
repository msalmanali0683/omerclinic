<?php

namespace App\Http\Requests;

use App\Models\DiagnosisMedicineTemplate;
use App\Support\MedicineTypes;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisMedicineTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DiagnosisMedicineTemplate::class);
    }

    public function rules(): array
    {
        return [
            'diagnosis_master_id'    => 'required|exists:diagnosis_masters,id',
            'medicine_id'            => 'nullable|exists:medicines,id',
            'mdcn_type'              => ['nullable', 'string', 'max:100', MedicineTypes::validationRule()],
            'mdcn_name'              => 'required|string|max:255',
            'mdcn_size'              => 'nullable|string|max:100',
            'mdcn_time_id'           => 'nullable|exists:medicine_dose_times,id',
            'mdcn_dose_from_meal_id' => 'nullable|exists:medicine_dose_from_meals,id',
            'sort_order'             => 'nullable|integer|min:0',
            'is_active'              => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            return;
        }

        $this->merge(['is_active' => true]);
    }
}
