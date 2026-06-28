<?php

namespace App\Policies;

use App\Models\Medicine;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicinePolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($ability === 'deleteDuplicates') {
            return $user->hasRole('super-admin');
        }

        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view medicines');
    }

    public function view(User $user, Medicine $medicine): bool
    {
        return $user->can('view medicines');
    }

    public function create(User $user): bool
    {
        return $user->can('create medicines');
    }

    public function update(User $user, Medicine $medicine): bool
    {
        return $user->can('edit medicines');
    }

    public function delete(User $user, Medicine $medicine): bool
    {
        return $user->can('delete medicines');
    }

    public function deleteDuplicates(User $user): bool
    {
        return $user->hasRole('super-admin');
    }
}
