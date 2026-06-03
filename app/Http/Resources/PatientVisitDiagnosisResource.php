<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVisitDiagnosisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'patient_visit_id'    => $this->patient_visit_id,
            'diagnosis_master_id' => $this->diagnosis_master_id,
            'diagnosis_text'      => $this->diagnosis_text,
            'diagnosis_master'    => $this->whenLoaded('diagnosisMaster', fn () => new DiagnosisMasterResource($this->diagnosisMaster)),
            'added_by'            => $this->whenLoaded('createdBy', fn () => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
