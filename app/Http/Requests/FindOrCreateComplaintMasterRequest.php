<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindOrCreateComplaintMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view complaint masters')
            || $this->user()->can('create visit complaints')
            || $this->user()->can('add complaints during prescription');
    }

    public function rules(): array
    {
        return [
            'complaint_name' => 'required|string|max:255',
        ];
    }
}
