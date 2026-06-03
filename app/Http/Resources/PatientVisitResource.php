<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'patient_id'       => $this->patient_id,
            'doctor_id'        => $this->doctor_id,
            'queued_by'        => $this->queued_by,
            'visit_date'       => $this->visit_date?->format('Y-m-d'),
            'visit_time'       => $this->visit_time,
            'status'           => $this->status,
            'reason_for_visit' => $this->reason_for_visit,
            'notes'            => $this->notes,
            'patient'          => new PatientResource($this->whenLoaded('patient')),
            'doctor'           => $this->whenLoaded('doctor', fn () => [
                'id'   => $this->doctor->id,
                'name' => $this->doctor->name,
            ]),
            'queued_by_user'   => $this->whenLoaded('queuedBy', fn () => [
                'id'   => $this->queuedBy->id,
                'name' => $this->queuedBy->name,
            ]),
            'created_at'       => $this->created_at?->toIso8601String(),
            ...$this->tokenFields($request),
        ];
    }

    protected function tokenFields(Request $request): array
    {
        if (! $this->relationLoaded('token')) {
            return [
                'has_token'         => false,
                'token_id'          => null,
                'token_number'      => null,
                'token_display'     => null,
                'token_date'        => null,
                'can_reprint_token' => false,
            ];
        }

        $token = $this->token;

        if (! $token) {
            return [
                'has_token'         => false,
                'token_id'          => null,
                'token_number'      => null,
                'token_display'     => null,
                'token_date'        => null,
                'can_reprint_token' => false,
            ];
        }

        return [
            'has_token'         => true,
            'token_id'          => $token->id,
            'token_number'      => $token->token_number,
            'token_display'     => $token->token_display ?? (string) $token->token_number,
            'token_date'        => $token->token_date?->format('Y-m-d'),
            'can_reprint_token' => $request->user()?->can('reprint patient tokens') ?? false,
        ];
    }
}
