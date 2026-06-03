<?php

namespace App\Http\Requests;

use App\Models\PatientVisit;
use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $prescription = $this->resolvePrescription();

        return $prescription && $this->user()->can('update', $prescription);
    }

    public function rules(): array
    {
        return [
            'notes'                              => 'nullable|string|max:2000',
            'next_visit_days'                    => 'nullable|integer|min:1|max:365',
            'medicines'                          => 'required|array|min:1',
            'medicines.*.id'                     => 'nullable|integer|exists:prescription_medicines,id',
            'medicines.*.medicine_id'            => 'nullable|integer|exists:medicines,id',
            'medicines.*.mdcn_type'              => 'nullable|string|max:100',
            'medicines.*.mdcn_name'              => 'required|string|max:255',
            'medicines.*.mdcn_size'              => 'nullable|string|max:100',
            'medicines.*.mdcn_time_id'           => 'nullable|integer|exists:medicine_dose_times,id',
            'medicines.*.mdcn_dose_from_meal_id' => 'nullable|integer|exists:medicine_dose_from_meals,id',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $prescription = $this->resolvePrescription();

            if (! $prescription) {
                return;
            }

            foreach ($this->input('medicines', []) as $index => $row) {
                if (empty($row['id'])) {
                    continue;
                }

                $belongsToPrescription = PrescriptionMedicine::query()
                    ->where('prescription_id', $prescription->id)
                    ->whereKey($row['id'])
                    ->exists();

                if (! $belongsToPrescription) {
                    $validator->errors()->add("medicines.{$index}.id", 'The medicine row does not belong to this prescription.');
                }
            }

            $visit = $prescription->visit ?? PatientVisit::find($prescription->patient_visit_id);

            if ($visit && $visit->status === PatientVisit::STATUS_CANCELLED) {
                if (! $this->user()->hasAnyRole(['super-admin', 'hospital-admin'])) {
                    $validator->errors()->add('medicines', 'Cannot update prescription for a cancelled visit.');
                }
            }
        });
    }

    protected function resolvePrescription(): ?Prescription
    {
        $prescription = $this->route('prescription');

        if ($prescription instanceof Prescription) {
            return $prescription;
        }

        $visit = $this->route('visit');

        if ($visit instanceof PatientVisit) {
            return $visit->prescription;
        }

        return null;
    }
}
