<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientVisitPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view patient queue')
            || $user->can('view patient visits')
            || $user->can('view limited patient visit history');
    }

    public function view(User $user, PatientVisit $visit): bool
    {
        if ($user->can('view full patient visit history')) {
            return true;
        }

        if ($user->can('view patient queue')) {
            if ($user->can('view all patient queue')) {
                return true;
            }

            if ($user->hasRole('doctor')) {
                return (int) $visit->doctor_id === (int) $user->id;
            }

            return true;
        }

        if ($user->can('view patient visits') || $user->can('view limited patient visit history')) {
            if ($user->hasRole('doctor')) {
                return (int) $visit->doctor_id === (int) $user->id;
            }

            return true;
        }

        return false;
    }

    public function viewHistory(User $user, Patient $patient): bool
    {
        if (! $user->can('view patient visits') && ! $user->can('view limited patient visit history')) {
            return false;
        }

        if ($user->can('view full patient visit history')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $user->id)
                ->exists();
        }

        return true;
    }

    public function viewDetails(User $user, PatientVisit $visit): bool
    {
        if (! $user->can('view patient visit details') && ! $user->can('view limited patient visit history')) {
            return false;
        }

        if ($user->can('view full patient visit history')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return (int) $visit->doctor_id === (int) $user->id;
        }

        if ($user->can('view limited patient visit history') && ! $user->can('view patient visit details')) {
            return true;
        }

        if ($user->hasRole('pharmacist')) {
            return $user->can('view prescriptions');
        }

        return $user->can('view patient visit details');
    }

    public function addToQueue(User $user): bool
    {
        return $user->can('add patient to queue');
    }

    public function assignDoctor(User $user, PatientVisit $visit): bool
    {
        if (! $user->can('assign doctor to queue')) {
            return false;
        }

        if ($user->hasRole('doctor') && (int) $visit->doctor_id !== (int) $user->id) {
            return false;
        }

        return true;
    }

    public function startConsultation(User $user, PatientVisit $visit): bool
    {
        if (! $user->can('start consultation')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return (int) $visit->doctor_id === (int) $user->id;
        }

        return true;
    }

    public function markPrescribed(User $user, PatientVisit $visit): bool
    {
        if (! $user->can('mark patient prescribed')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return (int) $visit->doctor_id === (int) $user->id;
        }

        return true;
    }

    public function cancel(User $user, PatientVisit $visit): bool
    {
        return $user->can('cancel patient queue');
    }

    public function returnToPendingPrescription(User $user, PatientVisit $visit): bool
    {
        if (! $user->can('return visit to pending prescription')) {
            return false;
        }

        if ($user->hasRole('doctor') && (int) $visit->doctor_id !== (int) $user->id) {
            return false;
        }

        return true;
    }
}
