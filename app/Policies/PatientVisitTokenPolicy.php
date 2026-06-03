<?php

namespace App\Policies;

use App\Models\PatientVisit;
use App\Models\PatientVisitToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientVisitTokenPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('hospital-admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view patient tokens');
    }

    public function view(User $user, PatientVisitToken $patientVisitToken): bool
    {
        return $user->can('view patient tokens');
    }

    public function generate(User $user, PatientVisit $visit): bool
    {
        return $user->can('generate patient tokens');
    }

    public function print(User $user, PatientVisitToken $patientVisitToken): bool
    {
        return $user->can('print patient tokens');
    }

    public function reprint(User $user, PatientVisitToken $patientVisitToken): bool
    {
        return $user->can('reprint patient tokens');
    }
}
