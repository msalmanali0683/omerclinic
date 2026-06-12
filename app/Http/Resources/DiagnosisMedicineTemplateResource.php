<?php

namespace App\Http\Resources;

use App\Support\MedicineTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisMedicineTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'diagnosis_master_id'    => $this->diagnosis_master_id,
            'medicine_id'            => $this->medicine_id,
            'mdcn_type'              => MedicineTypes::normalize($this->mdcn_type),
            'mdcn_name'              => $this->mdcn_name,
            'mdcn_size'              => $this->mdcn_size,
            'mdcn_time_id'           => $this->mdcn_time_id,
            'mdcn_dose_from_meal_id' => $this->mdcn_dose_from_meal_id,
            'dose_time_text'         => $this->doseTime?->dose_time,
            'dose_from_meal_text'    => $this->doseFromMeal?->dose_from_meal,
            'sort_order'             => $this->sort_order,
            'is_active'              => $this->is_active,
            'diagnosis'              => $this->whenLoaded('diagnosis', fn () => [
                'id'             => $this->diagnosis->id,
                'diagnosis_name' => $this->diagnosis->diagnosis_name,
            ]),
            'medicine'               => $this->whenLoaded('medicine', fn () => [
                'id'    => $this->medicine->id,
                'label' => $this->medicine->displayLabel(),
            ]),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
