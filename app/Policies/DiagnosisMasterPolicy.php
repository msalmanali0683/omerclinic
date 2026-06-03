<?php

namespace App\Policies;

use App\Models\DiagnosisMaster;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiagnosisMasterPolicy
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
        return $user->can('view diagnosis masters');
    }

    public function view(User $user, DiagnosisMaster $diagnosisMaster): bool
    {
        return $user->can('view diagnosis masters');
    }

    public function create(User $user): bool
    {
        return $user->can('create diagnosis masters');
    }

    public function update(User $user, DiagnosisMaster $diagnosisMaster): bool
    {
        return $user->can('edit diagnosis masters');
    }

    public function delete(User $user, DiagnosisMaster $diagnosisMaster): bool
    {
        return $user->can('delete diagnosis masters');
    }
}
