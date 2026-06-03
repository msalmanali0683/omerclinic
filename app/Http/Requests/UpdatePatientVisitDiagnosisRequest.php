<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientVisitDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient_visit_diagnosis'));
    }

    public function rules(): array
    {
        return [
            'diagnosis_master_id' => 'nullable|integer|exists:diagnosis_masters,id',
            'diagnosis_text'      => 'required|string|max:255',
        ];
    }
}
