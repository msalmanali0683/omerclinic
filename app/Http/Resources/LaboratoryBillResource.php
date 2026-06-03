<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'bill_no'          => $this->bill_no,
            'patient_id'       => $this->patient_id,
            'patient_visit_id' => $this->patient_visit_id,
            'subtotal'         => $this->subtotal,
            'discount'         => $this->discount,
            'total'            => $this->total,
            'status'           => $this->status,
            'patient'          => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'visit'            => $this->whenLoaded('visit', fn () => new PatientVisitResource($this->visit)),
            'results'          => LaboratoryResultResource::collection($this->whenLoaded('results')),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
