<?php

namespace App\Policies;

use App\Models\LaboratoryResult;
use App\Models\PatientVisit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaboratoryResultPolicy
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
        return $user->can('view laboratory results')
            || $user->can('view patient laboratory history');
    }

    public function view(User $user, LaboratoryResult $result): bool
    {
        if (! $user->can('view laboratory results') && ! $user->can('view patient laboratory history')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $this->canAccessResult($user, $result);
        }

        return $user->can('view laboratory results')
            || $user->can('view patient laboratory history');
    }

    public function create(User $user): bool
    {
        return $user->can('create laboratory results');
    }

    public function update(User $user, LaboratoryResult $result): bool
    {
        if (! $user->can('edit laboratory results')) {
            return false;
        }

        if ($user->hasAnyRole(['lab-technician', 'lab-operator'])) {
            return true;
        }

        if ((int) $result->created_by === (int) $user->id) {
            return true;
        }

        if ((int) $result->lab_operator_id === (int) $user->id) {
            return true;
        }

        return true;
    }

    public function delete(User $user, LaboratoryResult $result): bool
    {
        return $user->can('delete laboratory results') && $this->update($user, $result);
    }

    public function verify(User $user, LaboratoryResult $result): bool
    {
        return $user->can('verify laboratory results') && $this->view($user, $result);
    }

    public function print(User $user, LaboratoryResult $result): bool
    {
        if (! $user->can('print laboratory results')) {
            return false;
        }

        return $this->view($user, $result);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('view laboratory results')
            || $user->can('view patient laboratory history');
    }

    protected function canAccessResult(User $user, LaboratoryResult $result): bool
    {
        if (! $result->patient_visit_id) {
            return $result->visit()
                ->where('doctor_id', $user->id)
                ->exists()
                || PatientVisit::query()
                    ->where('patient_id', $result->patient_id)
                    ->where('doctor_id', $user->id)
                    ->exists();
        }

        if ($result->relationLoaded('visit') && $result->visit) {
            return (int) $result->visit->doctor_id === (int) $user->id;
        }

        return $result->visit()
            ->where('doctor_id', $user->id)
            ->exists();
    }
}
