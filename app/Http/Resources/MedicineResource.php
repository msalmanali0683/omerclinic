<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'mdcn_type'              => $this->mdcn_type,
            'mdcn_name'              => $this->mdcn_name,
            'mdcn_size'              => $this->mdcn_size,
            'mdcn_time_id'           => $this->mdcn_time_id,
            'mdcn_dose_from_meal_id' => $this->mdcn_dose_from_meal_id,
            'dose_time'              => $this->whenLoaded('doseTime', fn () => $this->doseTime?->dose_time),
            'dose_from_meal'         => $this->whenLoaded('doseFromMeal', fn () => $this->doseFromMeal?->dose_from_meal),
            'dose_time_relation'     => $this->whenLoaded('doseTime', fn () => new MedicineDoseTimeResource($this->doseTime)),
            'dose_from_meal_relation' => $this->whenLoaded('doseFromMeal', fn () => new MedicineDoseFromMealResource($this->doseFromMeal)),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
