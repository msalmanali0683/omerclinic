<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVisitTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'patient_id'        => $this->patient_id,
            'patient_visit_id'  => $this->patient_visit_id,
            'token_date'        => $this->token_date?->format('Y-m-d'),
            'token_number'      => $this->token_number,
            'token_display'     => $this->token_display ?? (string) $this->token_number,
            'generated_by'      => $this->generated_by,
            'printed_at'        => $this->printed_at?->toIso8601String(),
            'reprint_count'     => $this->reprint_count,
            'last_reprinted_at' => $this->last_reprinted_at?->toIso8601String(),
            'created_at'        => $this->created_at?->toIso8601String(),
        ];
    }
}
