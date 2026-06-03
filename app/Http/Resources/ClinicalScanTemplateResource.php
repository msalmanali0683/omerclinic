<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalScanTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'template_name' => $this->template_name,
            'description'   => $this->description,
            'is_active'     => $this->is_active,
            'fields'        => ClinicalScanTemplateFieldResource::collection($this->whenLoaded('fields')),
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
