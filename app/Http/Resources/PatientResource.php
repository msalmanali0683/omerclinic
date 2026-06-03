<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'mr_number'            => $this->mr_number,
            'patient_name'         => $this->patient_name,
            'patient_father_name'  => $this->patient_father_name,
            'patient_gender'       => $this->patient_gender,
            'patient_gender_label' => $this->genderLabel(),
            'patient_age'          => $this->patient_age,
            'patient_age_unit'     => $this->patient_age_unit ?? 'years',
            'patient_age_display'  => $this->ageDisplay(),
            'patient_cell'         => $this->patient_cell,
            'patient_address'      => $this->patient_address,
            'patient_cnic'         => $this->patient_cnic,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function genderLabel(): ?string
    {
        return match ($this->patient_gender) {
            'male'   => 'Male',
            'female' => 'Female',
            'other'  => 'Other',
            default  => null,
        };
    }

    protected function ageDisplay(): ?string
    {
        if ($this->patient_age === null) {
            return null;
        }

        $unit = match ($this->patient_age_unit) {
            'months' => 'Months',
            'days'   => 'Days',
            default  => 'Years',
        };

        return "{$this->patient_age} {$unit}";
    }
}
