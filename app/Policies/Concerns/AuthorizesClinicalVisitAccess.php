<?php

namespace App\Policies\Concerns;

use App\Models\PatientVisit;
use App\Models\User;

trait AuthorizesClinicalVisitAccess
{
    protected function canAccessVisit(User $user, ?PatientVisit $visit): bool
    {
        if (! $visit) {
            return false;
        }

        if ($visit->status === PatientVisit::STATUS_CANCELLED) {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'hospital-admin', 'receptionist', 'data-entry-operator'])) {
            return true;
        }

        if ($user->hasRole('nurse')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return (int) $visit->doctor_id === (int) $user->id;
        }

        return false;
    }
}
