<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'diagnosis_name' => $this->diagnosis_name,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
