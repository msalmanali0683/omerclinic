<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVisitComplaintResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'patient_id'          => $this->patient_id,
            'patient_visit_id'    => $this->patient_visit_id,
            'complaint_master_id' => $this->complaint_master_id,
            'complaint_text'      => $this->complaint_text,
            'complaint_master'    => $this->whenLoaded('complaintMaster', fn () => new ComplaintMasterResource($this->complaintMaster)),
            'added_by'            => $this->whenLoaded('createdBy', fn () => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
