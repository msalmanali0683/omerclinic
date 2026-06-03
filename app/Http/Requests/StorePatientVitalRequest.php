<?php

namespace App\Http\Requests;

use App\Models\PatientVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePatientVitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PatientVital::class);
    }

    public function rules(): array
    {
        return [
            'patient_id'        => 'required|integer|exists:patients,id',
            'patient_visit_id'  => 'required|integer|exists:patient_visits,id',
            'blood_pressure'    => 'nullable|string|max:30',
            'temperature'       => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'pulse_rate'        => 'nullable|integer',
            'respiratory_rate'  => 'nullable|integer',
            'notes'             => 'nullable|string|max:1000',
            'recorded_at'       => 'nullable|date',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $visit = PatientVisit::find($this->patient_visit_id);

            if (! $visit) {
                return;
            }

            if ((int) $visit->patient_id !== (int) $this->patient_id) {
                $validator->errors()->add('patient_visit_id', 'The visit does not belong to this patient.');
            }

            if ($visit->status === PatientVisit::STATUS_CANCELLED) {
                $validator->errors()->add('patient_visit_id', 'Cannot add vitals to a cancelled visit.');
            }
        });
    }
}
