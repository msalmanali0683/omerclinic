<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindOrCreateDiagnosisMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view diagnosis masters')
            || $this->user()->can('create visit diagnosis')
            || $this->user()->can('add diagnosis during prescription');
    }

    public function rules(): array
    {
        return [
            'diagnosis_name' => 'required|string|max:255',
        ];
    }
}
