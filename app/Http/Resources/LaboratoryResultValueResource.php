<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryResultValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $previewUrl = null;

        if ($this->field_type === 'image' && filled($this->field_value) && is_numeric($this->field_value)) {
            $previewUrl = route('laboratory-results.attachments.preview', [
                'laboratoryResult' => $this->laboratory_result_id,
                'attachment'       => (int) $this->field_value,
            ]);
        }

        return [
            'id'                              => $this->id,
            'laboratory_test_template_field_id' => $this->laboratory_test_template_field_id,
            'field_label'                     => $this->field_label,
            'field_key'                       => $this->field_key,
            'field_type'                      => $this->field_type,
            'field_value'                     => $this->field_value,
            'preview_url'                     => $previewUrl,
            'unit'                            => $this->unit,
            'reference_range'                 => $this->reference_range,
            'sort_order'                      => $this->sort_order,
        ];
    }
}
