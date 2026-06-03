<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryResultValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                              => $this->id,
            'laboratory_test_template_field_id' => $this->laboratory_test_template_field_id,
            'field_label'                     => $this->field_label,
            'field_key'                       => $this->field_key,
            'field_type'                      => $this->field_type,
            'field_value'                     => $this->field_value,
            'unit'                            => $this->unit,
            'reference_range'                 => $this->reference_range,
            'sort_order'                      => $this->sort_order,
        ];
    }
}
