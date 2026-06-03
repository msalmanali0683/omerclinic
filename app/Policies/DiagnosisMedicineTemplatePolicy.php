<?php

namespace App\Policies;

use App\Models\DiagnosisMedicineTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiagnosisMedicineTemplatePolicy
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
        return $user->can('view diagnosis medicine templates');
    }

    public function view(User $user, DiagnosisMedicineTemplate $diagnosisMedicineTemplate): bool
    {
        return $user->can('view diagnosis medicine templates');
    }

    public function create(User $user): bool
    {
        return $user->can('create diagnosis medicine templates');
    }

    public function update(User $user, DiagnosisMedicineTemplate $diagnosisMedicineTemplate): bool
    {
        return $user->can('edit diagnosis medicine templates');
    }

    public function delete(User $user, DiagnosisMedicineTemplate $diagnosisMedicineTemplate): bool
    {
        return $user->can('delete diagnosis medicine templates');
    }

    public function useInPrescription(User $user): bool
    {
        return $user->can('use diagnosis medicine templates in prescription');
    }
}
