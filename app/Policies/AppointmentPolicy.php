<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['hospital-admin', 'receptionist', 'doctor', 'patient']);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('hospital-admin') || $user->hasRole('receptionist')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $appointment->doctor_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            return $appointment->patient->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hospital-admin') || $user->hasRole('receptionist') || $user->can('create appointments');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('hospital-admin') || $user->hasRole('receptionist') || $user->can('edit appointments');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('hospital-admin') || $user->hasRole('receptionist') || $user->can('cancel appointments');
    }
}
