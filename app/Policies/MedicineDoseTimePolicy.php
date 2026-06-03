<?php

namespace App\Policies;

use App\Models\MedicineDoseTime;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicineDoseTimePolicy
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
        return $user->can('view medicine dose times');
    }

    public function view(User $user, MedicineDoseTime $medicineDoseTime): bool
    {
        return $user->can('view medicine dose times');
    }

    public function create(User $user): bool
    {
        return $user->can('create medicine dose times');
    }

    public function update(User $user, MedicineDoseTime $medicineDoseTime): bool
    {
        return $user->can('edit medicine dose times');
    }

    public function delete(User $user, MedicineDoseTime $medicineDoseTime): bool
    {
        return $user->can('delete medicine dose times');
    }
}
