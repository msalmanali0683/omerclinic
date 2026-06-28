<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PatientQueueService
{
    public function findActiveVisitToday(Patient $patient): ?PatientVisit
    {
        return PatientVisit::query()
            ->where('patient_id', $patient->id)
            ->whereDate('visit_date', today())
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->first();
    }

    /**
     * @return array{visit: PatientVisit, created: bool, message: string}
     */
    public function addToQueue(Patient $patient, User $user, array $data = []): array
    {
        return DB::transaction(function () use ($patient, $user, $data) {
            $doctorId = $data['doctor_id'] ?? null;
            $existing = $this->findActiveVisitToday($patient);

            if ($existing) {
                if ($doctorId !== null && $existing->doctor_id === null) {
                    $existing->update([
                        'doctor_id'  => $doctorId,
                        'updated_by' => $user->id,
                    ]);
                    $existing = $existing->fresh(['patient', 'doctor', 'queuedBy']);
                }

                $message = ($doctorId !== null && (int) $doctorId === (int) $user->id)
                    ? 'Patient is already in your queue.'
                    : 'Patient is already in queue.';

                return [
                    'visit'   => $existing->load(['patient', 'doctor', 'queuedBy']),
                    'created' => false,
                    'message' => $message,
                ];
            }

            $visit = PatientVisit::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $doctorId,
                'queued_by'        => $user->id,
                'visit_date'       => $data['visit_date'] ?? today()->toDateString(),
                'visit_time'       => $data['visit_time'] ?? now()->format('H:i:s'),
                'status'           => PatientVisit::STATUS_PENDING,
                'reason_for_visit' => $data['reason_for_visit'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'created_by'       => $user->id,
                'updated_by'       => $user->id,
            ]);

            return [
                'visit'   => $visit->load(['patient', 'doctor', 'queuedBy']),
                'created' => true,
                'message' => $doctorId && (int) $doctorId === (int) $user->id
                    ? 'Patient added to your queue successfully.'
                    : 'Patient added to queue successfully.',
            ];
        });
    }

    public function returnToPendingPrescription(PatientVisit $visit, User $user): PatientVisit
    {
        if ($visit->status !== PatientVisit::STATUS_PRESCRIBED) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only prescribed visits can be returned to pending prescription.',
            ]);
        }

        if (! $visit->visit_date?->isSameDay(today())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'visit_date' => 'Only same-day visits can be returned to the doctor queue.',
            ]);
        }

        $visit->update([
            'status'     => PatientVisit::STATUS_PENDING,
            'updated_by' => $user->id,
        ]);

        return $visit->fresh(['patient', 'doctor', 'queuedBy']);
    }

    public function cancelActiveVisitsForPatient(Patient $patient, ?User $user = null): int
    {
        return PatientVisit::query()
            ->where('patient_id', $patient->id)
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->update([
                'status'     => PatientVisit::STATUS_CANCELLED,
                'updated_by' => $user?->id,
            ]);
    }

    public function cancelActiveVisitsWithDeletedPatients(?User $user = null): int
    {
        return PatientVisit::query()
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->whereHas('patient', fn ($query) => $query->onlyTrashed())
            ->update([
                'status'     => PatientVisit::STATUS_CANCELLED,
                'updated_by' => $user?->id,
            ]);
    }

    /**
     * Cancel active queue visits from previous days.
     */
    public function cancelStaleQueueVisits(?User $user = null): int
    {
        $staleCount = PatientVisit::query()
            ->whereDate('visit_date', '<', today())
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->update([
                'status'     => PatientVisit::STATUS_CANCELLED,
                'updated_by' => $user?->id,
            ]);

        $deletedPatientCount = $this->cancelActiveVisitsWithDeletedPatients($user);

        return $staleCount + $deletedPatientCount;
    }

    public function countStaleQueueVisits(): int
    {
        $staleCount = PatientVisit::query()
            ->whereDate('visit_date', '<', today())
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->count();

        $deletedPatientCount = PatientVisit::query()
            ->whereIn('status', PatientVisit::ACTIVE_STATUSES)
            ->whereHas('patient', fn ($query) => $query->onlyTrashed())
            ->count();

        return $staleCount + $deletedPatientCount;
    }
}
