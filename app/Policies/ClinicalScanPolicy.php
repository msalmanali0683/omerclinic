<?php

namespace App\Policies;

use App\Models\ClinicalScan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClinicalScanPolicy
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
        return $user->can('view clinical scans')
            || $user->can('view patient clinical scan history');
    }

    public function view(User $user, ClinicalScan $scan): bool
    {
        if (! $user->can('view clinical scans') && ! $user->can('view patient clinical scan history')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $this->canAccessScan($user, $scan);
        }

        if ($user->hasRole('scan-operator')) {
            return $user->can('view clinical scans');
        }

        return $user->can('view clinical scans');
    }

    public function create(User $user): bool
    {
        return $user->can('create clinical scans');
    }

    public function update(User $user, ClinicalScan $scan): bool
    {
        if (! $user->can('edit clinical scans')) {
            return false;
        }

        if ($user->hasRole('scan-operator')) {
            return true;
        }

        if ((int) $scan->created_by === (int) $user->id) {
            return true;
        }

        if ((int) $scan->scan_operator_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole('doctor')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, ClinicalScan $scan): bool
    {
        return $user->can('delete clinical scans') && $this->update($user, $scan);
    }

    public function print(User $user, ClinicalScan $scan): bool
    {
        if (! $user->can('print clinical scans')) {
            return false;
        }

        return $this->view($user, $scan);
    }

    public function viewHistory(User $user): bool
    {
        return $user->can('view clinical scans')
            || $user->can('view patient clinical scan history');
    }

    protected function canAccessScan(User $user, ClinicalScan $scan): bool
    {
        if ((int) $scan->scan_operator_id === (int) $user->id) {
            return true;
        }

        if ($scan->relationLoaded('visit') && $scan->visit) {
            return (int) $scan->visit->doctor_id === (int) $user->id;
        }

        return $scan->visit()
            ->where('doctor_id', $user->id)
            ->exists();
    }
}
