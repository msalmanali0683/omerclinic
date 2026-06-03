<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientVisitComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient_visit_complaint'));
    }

    public function rules(): array
    {
        return [
            'complaint_master_id' => 'nullable|integer|exists:complaint_masters,id',
            'complaint_text'      => 'required|string|max:255',
        ];
    }
}
