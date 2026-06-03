<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrescriptionPolicy
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
        return $user->can('view prescriptions');
    }

    public function view(User $user, Prescription $prescription): bool
    {
        if (! $user->can('view prescriptions')) {
            return false;
        }

        if ($user->hasRole('pharmacist')) {
            return true;
        }

        if ($user->hasRole('patient')) {
            return $prescription->patient?->user_id === $user->id;
        }

        return $this->canAccessPrescription($user, $prescription);
    }

    public function create(User $user): bool
    {
        return $user->can('create prescription');
    }

    public function update(User $user, Prescription $prescription): bool
    {
        if (! $user->can('edit prescription')
            && ! $user->can('update prescription')
            && ! $user->can('re-prescribe prescription')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $this->canAccessPrescription($user, $prescription);
        }

        return false;
    }

    public function rePrescribe(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $user->can('delete prescription') && $this->update($user, $prescription);
    }

    public function print(User $user, Prescription $prescription): bool
    {
        if (! $user->can('print prescription')) {
            return false;
        }

        return $this->canAccessPrescription($user, $prescription);
    }

    public function dispense(User $user, Prescription $prescription): bool
    {
        return $user->can('dispense medicine');
    }

    protected function canAccessPrescription(User $user, Prescription $prescription): bool
    {
        if ((int) $prescription->created_by === (int) $user->id) {
            return true;
        }

        if ((int) $prescription->doctor_id === (int) $user->id) {
            return true;
        }

        if ($prescription->relationLoaded('visit') && $prescription->visit) {
            return (int) $prescription->visit->doctor_id === (int) $user->id;
        }

        return $prescription->visit()
            ->where('doctor_id', $user->id)
            ->exists();
    }
}
