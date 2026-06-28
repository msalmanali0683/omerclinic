<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'restore') {
            return $user->hasRole('super-admin');
        }

        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view patients')
            || $user->can('view limited patient info')
            || $user->can('search patients');
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->can('view patients')) {
            return true;
        }

        if ($user->can('view assigned patients') && $user->hasRole('doctor')) {
            return PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $user->id)
                ->exists();
        }

        if ($user->can('view assigned patients') && $patient->doctor_id === $user->id) {
            return true;
        }

        if ($user->can('view limited patient info')) {
            return true;
        }

        if ($user->hasRole('patient')) {
            return $patient->user_id === $user->id;
        }

        return false;
    }

    public function search(User $user): bool
    {
        return $user->can('search patients');
    }

    public function create(User $user): bool
    {
        return $user->can('create patients');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('edit patients');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->can('delete patients');
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole('super-admin');
    }

    public function viewMedicalHistory(User $user, Patient $patient): bool
    {
        if ($user->hasRole('hospital-admin')) {
            return true;
        }

        if ($user->can('view patient medical history') && $user->hasRole('doctor')) {
            return PatientVisit::query()
                ->where('patient_id', $patient->id)
                ->where('doctor_id', $user->id)
                ->exists();
        }

        return $user->can('view patient medical history');
    }
}
