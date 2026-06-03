<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintMasterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ComplaintMaster::class);
    }

    public function rules(): array
    {
        return [
            'complaint_name' => 'required|string|max:255|unique:complaint_masters,complaint_name',
        ];
    }
}
