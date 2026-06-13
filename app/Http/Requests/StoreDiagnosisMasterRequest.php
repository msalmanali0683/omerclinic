<?php

namespace App\Http\Requests;

use App\Support\ActiveRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiagnosisMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\DiagnosisMaster::class);
    }

    public function rules(): array
    {
        return [
            'diagnosis_name' => ['required', 'string', 'max:255', ActiveRecordValidation::unique('diagnosis_masters', 'diagnosis_name')],
        ];
    }
}
