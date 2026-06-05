<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryResultAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'laboratory_result_id'       => $this->laboratory_result_id,
            'laboratory_result_value_id' => $this->laboratory_result_value_id,
            'original_name'              => $this->original_name,
            'mime_type'                  => $this->mime_type,
            'file_size'                  => $this->file_size,
            'preview_url'                => route('laboratory-results.attachments.preview', [
                'laboratoryResult' => $this->laboratory_result_id,
                'attachment'       => $this->id,
            ]),
            'created_at'                 => $this->created_at?->toIso8601String(),
        ];
    }
}
