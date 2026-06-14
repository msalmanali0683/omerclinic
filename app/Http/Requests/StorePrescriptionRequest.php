<?php

namespace App\Http\Requests;

use App\Models\PatientVisit;
use App\Support\MedicineTypes;
use App\Support\PatientValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Prescription::class);
    }

    public function rules(): array
    {
        return [
            'patient_id'                         => 'required|integer|exists:patients,id',
            'patient_visit_id'                   => 'required|integer|exists:patient_visits,id',
            'notes'                              => 'nullable|string|max:2000',
            'next_visit_days'                    => 'nullable|integer|min:1|max:365',
            'medicines'                          => 'nullable|array',
            'medicines.*.medicine_id'            => 'nullable|integer|exists:medicines,id',
            'medicines.*.mdcn_type'              => 'nullable|string|max:100',
            'medicines.*.mdcn_name'              => 'required|string|max:255',
            'medicines.*.mdcn_size'              => 'nullable|string|max:100',
            'medicines.*.mdcn_time_id'           => 'nullable|integer|exists:medicine_dose_times,id',
            'medicines.*.mdcn_dose_from_meal_id' => 'nullable|integer|exists:medicine_dose_from_meals,id',
            'medicines.*.show_in_treatment_given' => 'nullable|boolean',
            ...PatientValidationRules::nestedProfile((int) $this->input('patient_id')),
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
                $validator->errors()->add('patient_visit_id', 'Cannot create prescription for a cancelled visit.');
            }

            if ($this->user()->hasRole('doctor') && (int) $visit->doctor_id !== (int) $this->user()->id) {
                if (! $this->user()->can('view all patient queue')) {
                    $validator->errors()->add('patient_visit_id', 'You are not assigned to this patient visit.');
                }
            }

            foreach ($this->input('medicines', []) as $index => $medicine) {
                $message = MedicineTypes::validateNewMasterRow(
                    isset($medicine['medicine_id']) ? (string) $medicine['medicine_id'] : null,
                    $medicine['mdcn_name'] ?? null,
                    $medicine['mdcn_type'] ?? null,
                );

                if ($message !== null) {
                    $validator->errors()->add("medicines.{$index}.mdcn_type", $message);
                }
            }
        });
    }
}
