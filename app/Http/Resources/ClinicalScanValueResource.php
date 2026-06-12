<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalScanValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                              => $this->id,
            'clinical_scan_template_field_id' => $this->clinical_scan_template_field_id,
            'field_label'                     => $this->field_label,
            'group_label'                     => $this->group_label,
            'field_key'                       => $this->field_key,
            'field_type'                      => $this->field_type,
            'field_value'                     => $this->field_value,
            'sort_order'                      => $this->sort_order,
        ];
    }
}
