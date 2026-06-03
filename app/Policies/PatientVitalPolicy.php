<?php

namespace App\Policies;

use App\Models\PatientVital;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientVitalPolicy
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
        return $user->can('view patient vitals');
    }

    public function view(User $user, PatientVital $vital): bool
    {
        if (! $user->can('view patient vitals')) {
            return false;
        }

        return $this->canAccessVisit($user, $vital->visit);
    }

    public function create(User $user): bool
    {
        return $user->can('create patient vitals');
    }

    public function update(User $user, PatientVital $vital): bool
    {
        if (! $user->can('edit patient vitals')) {
            return false;
        }

        return $this->canAccessVisit($user, $vital->visit);
    }

    public function delete(User $user, PatientVital $vital): bool
    {
        if (! $user->can('delete patient vitals')) {
            return false;
        }

        return $this->canAccessVisit($user, $vital->visit);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('view previous patient vitals');
    }

    protected function canAccessVisit(User $user, ?PatientVisit $visit): bool
    {
        if (! $visit) {
            return true;
        }

        if ($user->hasAnyRole(['receptionist', 'nurse', 'data-entry-operator', 'hospital-admin'])) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $visit->doctor_id === null || $visit->doctor_id === $user->id;
        }

        return true;
    }
}
