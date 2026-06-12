<?php

namespace App\Http\Resources;

use App\Support\MedicineTypes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionMedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'prescription_id'        => $this->prescription_id,
            'medicine_id'            => $this->medicine_id,
            'mdcn_type'              => MedicineTypes::normalize($this->mdcn_type),
            'mdcn_name'              => $this->mdcn_name,
            'mdcn_size'              => $this->mdcn_size,
            'mdcn_time_id'           => $this->mdcn_time_id,
            'mdcn_dose_from_meal_id' => $this->mdcn_dose_from_meal_id,
            'dose_time_text'            => $this->dose_time_text,
            'dose_from_meal_text'       => $this->dose_from_meal_text,
            'show_in_treatment_given'   => (bool) $this->show_in_treatment_given,
            'dose_time'              => $this->whenLoaded('doseTime', fn () => $this->doseTime?->dose_time),
            'dose_from_meal'         => $this->whenLoaded('doseFromMeal', fn () => $this->doseFromMeal?->dose_from_meal),
        ];
    }
}
