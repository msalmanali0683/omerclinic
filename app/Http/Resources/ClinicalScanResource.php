<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'patient_id'                => $this->patient_id,
            'patient_visit_id'          => $this->patient_visit_id,
            'clinical_scan_template_id' => $this->clinical_scan_template_id,
            'scan_template_name'        => $this->scan_template_name,
            'scan_name'                 => $this->scan_name,
            'scan_operator_id'          => $this->scan_operator_id,
            'scan_date'                 => $this->scan_date?->format('Y-m-d'),
            'scan_time'                 => $this->scan_time,
            'status'                    => $this->status,
            'notes'                     => $this->notes,
            'impression'                => $this->impression,
            'patient'                   => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'visit'                     => $this->whenLoaded('visit', fn () => new PatientVisitResource($this->visit)),
            'template'                  => $this->whenLoaded('template', fn () => new ClinicalScanTemplateResource($this->template)),
            'scan_operator'             => $this->whenLoaded('scanOperator', fn () => [
                'id'   => $this->scanOperator->id,
                'name' => $this->scanOperator->name,
            ]),
            'values'                    => ClinicalScanValueResource::collection($this->whenLoaded('values')),
            'created_at'                => $this->created_at?->toIso8601String(),
        ];
    }
}
