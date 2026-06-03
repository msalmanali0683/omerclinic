<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'complaint_name' => $this->complaint_name,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
