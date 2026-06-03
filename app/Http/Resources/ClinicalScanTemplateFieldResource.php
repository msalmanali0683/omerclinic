<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicalScanTemplateFieldResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'field_label'    => $this->field_label,
            'field_key'      => $this->field_key,
            'field_type'     => $this->field_type,
            'options'        => $this->options,
            'default_value'  => $this->default_value,
            'placeholder'    => $this->placeholder,
            'is_required'    => $this->is_required,
            'sort_order'     => $this->sort_order,
        ];
    }
}
