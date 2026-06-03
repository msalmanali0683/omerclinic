<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryTestTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'test_name'   => $this->test_name,
            'test_code'   => $this->test_code,
            'test_price'  => $this->test_price,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'fields'      => LaboratoryTestTemplateFieldResource::collection($this->whenLoaded('fields')),
            'fields_count'=> $this->when(isset($this->fields_count), $this->fields_count),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
