<?php

namespace App\Http\Requests;

use App\Models\PatientVisit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLaboratoryBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create lab bills');
    }

    public function rules(): array
    {
        return [
            'patient_id'         => 'required|integer|exists:patients,id',
            'patient_visit_id' => 'nullable|integer|exists:patient_visits,id',
            'test_items'                  => 'required|array|min:1',
            'test_items.*.template_id'    => 'required|integer|distinct|exists:laboratory_test_templates,id',
            'test_items.*.test_price'     => 'nullable|numeric|min:0|max:99999999.99',
            'discount'           => 'nullable|numeric|min:0|max:99999999.99',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->filled('patient_visit_id')) {
                return;
            }

            $visit = PatientVisit::find($this->patient_visit_id);

            if ($visit && (int) $visit->patient_id !== (int) $this->patient_id) {
                $validator->errors()->add('patient_visit_id', 'The visit does not belong to this patient.');
            }
        });
    }
}
