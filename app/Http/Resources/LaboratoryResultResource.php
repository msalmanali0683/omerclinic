<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'patient_id'                  => $this->patient_id,
            'patient_visit_id'            => $this->patient_visit_id,
            'laboratory_test_template_id' => $this->laboratory_test_template_id,
            'test_name'                   => $this->test_name,
            'test_code'                   => $this->test_code,
            'lab_operator_id'             => $this->lab_operator_id,
            'result_date'                 => $this->result_date?->format('Y-m-d'),
            'result_time'                 => $this->result_time,
            'status'                      => $this->status,
            'remarks'                     => $this->remarks,
            'patient'                     => $this->whenLoaded('patient', fn () => new PatientResource($this->patient)),
            'visit'                       => $this->whenLoaded('visit', fn () => new PatientVisitResource($this->visit)),
            'template'                    => $this->whenLoaded('template', fn () => new LaboratoryTestTemplateResource($this->template)),
            'lab_operator'                => $this->whenLoaded('labOperator', fn () => [
                'id'   => $this->labOperator->id,
                'name' => $this->labOperator->name,
            ]),
            'values'                      => LaboratoryResultValueResource::collection($this->whenLoaded('values')),
            'created_at'                  => $this->created_at?->toIso8601String(),
        ];
    }
}
