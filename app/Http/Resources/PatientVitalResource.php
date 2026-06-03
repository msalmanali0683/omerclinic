<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVitalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'patient_id'        => $this->patient_id,
            'patient_visit_id'  => $this->patient_visit_id,
            'blood_pressure'    => $this->blood_pressure,
            'temperature'       => $this->temperature,
            'weight'            => $this->weight,
            'pulse_rate'        => $this->pulse_rate,
            'respiratory_rate'  => $this->respiratory_rate,
            'notes'             => $this->notes,
            'recorded_at'       => $this->recorded_at?->toIso8601String(),
            'recorded_by'       => $this->whenLoaded('recordedBy', fn () => [
                'id'   => $this->recordedBy->id,
                'name' => $this->recordedBy->name,
            ]),
            'visit'             => $this->whenLoaded('visit', fn () => [
                'id'         => $this->visit->id,
                'visit_date' => $this->visit->visit_date?->format('Y-m-d'),
                'status'     => $this->visit->status,
            ]),
            'patient'           => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
