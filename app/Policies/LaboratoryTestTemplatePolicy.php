<?php

namespace App\Policies;

use App\Models\LaboratoryTestTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaboratoryTestTemplatePolicy
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
            || $user->can('view laboratory test templates');
    }

    public function view(User $user, LaboratoryTestTemplate $template): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('create laboratory test templates');
    }

    public function update(User $user, LaboratoryTestTemplate $template): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('edit laboratory test templates');
    }

    public function delete(User $user, LaboratoryTestTemplate $template): bool
    {
        return $user->hasRole('hospital-admin')
            || $user->can('delete laboratory test templates');
    }
}
