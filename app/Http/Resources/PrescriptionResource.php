<?php

namespace App\Http\Resources;

use App\Support\PrescriptionFollowUp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'patient_id'         => $this->patient_id,
            'patient_visit_id'   => $this->patient_visit_id,
            'doctor_id'          => $this->doctor_id,
            'prescription_date'  => $this->prescription_date?->toDateString(),
            'next_visit_days'    => $this->next_visit_days,
            'next_visit_date'    => $this->next_visit_date?->toDateString(),
            'next_visit_text_urdu' => PrescriptionFollowUp::urduText($this->next_visit_days),
            'diagnosis'          => $this->diagnosis,
            'notes'              => $this->notes,
            'status'             => $this->status,
            'is_dispensed'       => $this->is_dispensed,
            'medicines'          => PrescriptionMedicineResource::collection($this->whenLoaded('medicineItems')),
            'patient'            => $this->whenLoaded('patient'),
            'doctor'             => $this->whenLoaded('doctor'),
            'visit'              => $this->whenLoaded('visit'),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
