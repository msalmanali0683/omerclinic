<?php

namespace App\Policies;

use App\Models\MedicineDoseFromMeal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicineDoseFromMealPolicy
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
        return $user->can('view medicine dose from meals');
    }

    public function view(User $user, MedicineDoseFromMeal $medicineDoseFromMeal): bool
    {
        return $user->can('view medicine dose from meals');
    }

    public function create(User $user): bool
    {
        return $user->can('create medicine dose from meals');
    }

    public function update(User $user, MedicineDoseFromMeal $medicineDoseFromMeal): bool
    {
        return $user->can('edit medicine dose from meals');
    }

    public function delete(User $user, MedicineDoseFromMeal $medicineDoseFromMeal): bool
    {
        return $user->can('delete medicine dose from meals');
    }
}
