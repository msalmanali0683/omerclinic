<?php

namespace App\Http\Requests;

use App\Models\Medicine;
use App\Support\MedicineTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Medicine::class);
    }

    public function rules(): array
    {
        return [
            'mdcn_type'              => ['required', 'string', 'max:100', MedicineTypes::validationRule()],
            'mdcn_name'              => 'required|string|max:255',
            'mdcn_size'              => 'nullable|string|max:100',
            'mdcn_time_id'           => 'nullable|integer|exists:medicine_dose_times,id',
            'mdcn_dose_from_meal_id' => 'nullable|integer|exists:medicine_dose_from_meals,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->duplicateMedicineExists()) {
                $validator->errors()->add('mdcn_name', 'A medicine with this type, name, and size already exists.');
            }
        });
    }

    protected function duplicateMedicineExists(?int $ignoreId = null): bool
    {
        $query = Medicine::query()
            ->where('mdcn_type', $this->mdcn_type)
            ->where('mdcn_name', $this->mdcn_name)
            ->where('mdcn_size', $this->mdcn_size ?? '');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
