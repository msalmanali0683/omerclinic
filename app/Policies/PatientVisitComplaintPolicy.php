<?php

namespace App\Policies;

use App\Models\PatientVisitComplaint;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalVisitAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientVisitComplaintPolicy
{
    use AuthorizesClinicalVisitAccess, HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view visit complaints');
    }

    public function view(User $user, PatientVisitComplaint $patientVisitComplaint): bool
    {
        if (! $user->can('view visit complaints')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitComplaint->visit);
    }

    public function create(User $user): bool
    {
        return $user->can('create visit complaints');
    }

    public function update(User $user, PatientVisitComplaint $patientVisitComplaint): bool
    {
        if (! $user->can('edit visit complaints')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitComplaint->visit);
    }

    public function delete(User $user, PatientVisitComplaint $patientVisitComplaint): bool
    {
        if (! $user->can('delete visit complaints')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitComplaint->visit);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('view visit complaints');
    }
}
