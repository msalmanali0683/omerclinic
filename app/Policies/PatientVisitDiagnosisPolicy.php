<?php

namespace App\Policies;

use App\Models\PatientVisitDiagnosis;
use App\Models\User;
use App\Policies\Concerns\AuthorizesClinicalVisitAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientVisitDiagnosisPolicy
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
        return $user->can('view visit diagnosis');
    }

    public function view(User $user, PatientVisitDiagnosis $patientVisitDiagnosis): bool
    {
        if (! $user->can('view visit diagnosis')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitDiagnosis->visit);
    }

    public function create(User $user): bool
    {
        return $user->can('create visit diagnosis');
    }

    public function update(User $user, PatientVisitDiagnosis $patientVisitDiagnosis): bool
    {
        if (! $user->can('edit visit diagnosis')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitDiagnosis->visit);
    }

    public function delete(User $user, PatientVisitDiagnosis $patientVisitDiagnosis): bool
    {
        if (! $user->can('delete visit diagnosis')) {
            return false;
        }

        return $this->canAccessVisit($user, $patientVisitDiagnosis->visit);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('view visit diagnosis');
    }
}
