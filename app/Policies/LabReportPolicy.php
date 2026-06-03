<?php

namespace App\Policies;

use App\Models\LabReport;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LabReportPolicy
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
        return $user->hasAnyRole(['hospital-admin', 'lab-manager', 'lab-technician', 'doctor', 'patient']);
    }

    public function view(User $user, LabReport $labReport): bool
    {
        if ($user->hasRole('hospital-admin') || $user->hasRole('lab-manager') || $user->hasRole('lab-technician')) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return $labReport->doctor_id === $user->id || $labReport->patient->doctor_id === $user->id;
        }

        if ($user->hasRole('patient')) {
            // patient can view their own approved lab reports only
            return $labReport->patient->user_id === $user->id && $labReport->is_approved;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['lab-technician', 'lab-manager']) || $user->can('create lab report');
    }

    public function update(User $user, LabReport $labReport): bool
    {
        return $user->hasAnyRole(['lab-technician', 'lab-manager']) || $user->can('edit lab report');
    }

    public function approve(User $user, LabReport $labReport): bool
    {
        return $user->hasRole('lab-manager') || $user->can('approve lab report');
    }
}
