<?php

namespace App\Policies;

use App\Models\ClinicalScanTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClinicalScanTemplatePolicy
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
        return $user->hasRole('hospital-admin')
            || $user->can('view clinical scan templates');
    }

    public function view(User $user, ClinicalScanTemplate $template): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('view clinical scan templates');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('create clinical scan templates');
    }

    public function update(User $user, ClinicalScanTemplate $template): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('edit clinical scan templates');
    }

    public function delete(User $user, ClinicalScanTemplate $template): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('delete clinical scan templates');
    }
}
