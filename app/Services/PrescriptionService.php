<?php

namespace App\Services;

use App\Models\MedicineDoseFromMeal;
use App\Models\MedicineDoseTime;
use App\Models\PatientVisit;
use App\Models\PatientVisitDiagnosis;
use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
use App\Models\User;
use App\Support\PrescriptionFollowUp;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    public function create(array $data, User $user): Prescription
    {
        return DB::transaction(function () use ($data, $user) {
            $existing = Prescription::query()
                ->where('patient_visit_id', $data['patient_visit_id'])
                ->first();

            if ($existing) {
                throw new \RuntimeException('duplicate_prescription:'.$existing->id);
            }

            $visit = PatientVisit::with('diagnoses')->findOrFail($data['patient_visit_id']);

            $diagnosisSummary = PatientVisitDiagnosis::query()
                ->where('patient_visit_id', $visit->id)
                ->pluck('diagnosis_text')
                ->filter()
                ->implode(', ');

            $doctorId = null;
            if ($user->hasRole('doctor')) {
                $doctorId = $user->id;
            } elseif ($visit->doctor_id) {
                $doctorId = $visit->doctor_id;
            } elseif (! empty($data['doctor_id'])) {
                $doctorId = (int) $data['doctor_id'];
            }

            $prescriptionDate = $data['prescription_date'] ?? now()->toDateString();

            $prescription = Prescription::create([
                'patient_id'        => $data['patient_id'],
                'patient_visit_id'  => $data['patient_visit_id'],
                'doctor_id'         => $doctorId,
                'prescription_date' => $prescriptionDate,
                ...PrescriptionFollowUp::resolveFields($data, $prescriptionDate),
                'diagnosis'         => $diagnosisSummary ?: null,
                'medicines'         => $this->buildLegacyMedicinesText($data['medicines']),
                'notes'             => $data['notes'] ?? null,
                'status'            => 'active',
                'created_by'        => $user->id,
                'updated_by'        => $user->id,
            ]);

            $this->syncMedicines($prescription, $data['medicines'], $user, replaceAll: true);

            $visit->update([
                'status'     => PatientVisit::STATUS_PRESCRIBED,
                'doctor_id'  => $visit->doctor_id ?? $user->id,
                'updated_by' => $user->id,
            ]);

            return $prescription->load(['medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine', 'patient', 'doctor', 'visit']);
        });
    }

    public function update(Prescription $prescription, array $data, User $user): Prescription
    {
        return DB::transaction(function () use ($prescription, $data, $user) {
            $prescription->loadMissing('visit.diagnoses');

            $diagnosisSummary = PatientVisitDiagnosis::query()
                ->where('patient_visit_id', $prescription->patient_visit_id)
                ->pluck('diagnosis_text')
                ->filter()
                ->implode(', ');

            $prescription->update([
                'notes'      => $data['notes'] ?? null,
                'medicines'  => $this->buildLegacyMedicinesText($data['medicines']),
                'diagnosis'  => $diagnosisSummary ?: null,
                ...PrescriptionFollowUp::resolveFields(
                    $data,
                    $prescription->prescription_date?->toDateString() ?? now()->toDateString()
                ),
                'updated_by' => $user->id,
            ]);

            $this->syncMedicines($prescription, $data['medicines'], $user, replaceAll: true);

            $visit = $prescription->visit;

            if ($visit && in_array($visit->status, PatientVisit::ACTIVE_STATUSES, true)) {
                $visit->update([
                    'status'     => PatientVisit::STATUS_PRESCRIBED,
                    'doctor_id'  => $visit->doctor_id ?? $user->id,
                    'updated_by' => $user->id,
                ]);
            }

            return $prescription->fresh(['medicineItems.doseTime', 'medicineItems.doseFromMeal', 'medicineItems.medicine', 'patient', 'doctor', 'visit']);
        });
    }

    protected function syncMedicines(Prescription $prescription, array $rows, User $user, bool $replaceAll = false): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $snapshot = $this->resolveSnapshot($row);

            if (! empty($row['id'])) {
                $item = PrescriptionMedicine::query()
                    ->where('prescription_id', $prescription->id)
                    ->whereKey($row['id'])
                    ->firstOrFail();

                $item->update([
                    ...$snapshot,
                    'updated_by' => $user->id,
                ]);
                $keptIds[] = $item->id;

                continue;
            }

            $item = PrescriptionMedicine::create([
                'prescription_id'  => $prescription->id,
                'patient_id'       => $prescription->patient_id,
                'patient_visit_id' => $prescription->patient_visit_id,
                ...$snapshot,
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ]);
            $keptIds[] = $item->id;
        }

        if ($replaceAll) {
            PrescriptionMedicine::query()
                ->where('prescription_id', $prescription->id)
                ->whereNotIn('id', $keptIds)
                ->each(fn (PrescriptionMedicine $item) => $item->delete());
        }
    }

    protected function resolveSnapshot(array $row): array
    {
        $doseTimeText = null;
        $doseFromMealText = null;

        if (! empty($row['mdcn_time_id'])) {
            $doseTimeText = MedicineDoseTime::find($row['mdcn_time_id'])?->dose_time;
        }

        if (! empty($row['mdcn_dose_from_meal_id'])) {
            $doseFromMealText = MedicineDoseFromMeal::find($row['mdcn_dose_from_meal_id'])?->dose_from_meal;
        }

        return [
            'medicine_id'            => $row['medicine_id'] ?? null,
            'mdcn_type'              => $row['mdcn_type'] ?? null,
            'mdcn_name'              => $row['mdcn_name'],
            'mdcn_size'              => $row['mdcn_size'] ?? null,
            'mdcn_time_id'           => $row['mdcn_time_id'] ?? null,
            'mdcn_dose_from_meal_id' => $row['mdcn_dose_from_meal_id'] ?? null,
            'dose_time_text'         => $doseTimeText,
            'dose_from_meal_text'    => $doseFromMealText,
        ];
    }

    protected function buildLegacyMedicinesText(array $rows): string
    {
        return collect($rows)->map(function (array $row) {
            $parts = array_filter([
                $row['mdcn_type'] ?? null,
                $row['mdcn_name'] ?? null,
                $row['mdcn_size'] ?? null,
            ]);

            $line = implode(' ', $parts);

            return trim($line);
        })->filter()->implode("\n");
    }
}
