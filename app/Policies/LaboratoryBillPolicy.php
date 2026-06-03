<?php

namespace App\Policies;

use App\Models\LaboratoryBill;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaboratoryBillPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super-admin', 'hospital-admin'])) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        return $user->can('create lab bills');
    }

    public function view(User $user, LaboratoryBill $bill): bool
    {
        return $user->can('create lab bills') || $user->can('print lab bills');
    }

    public function print(User $user, LaboratoryBill $bill): bool
    {
        return $user->can('print lab bills') && $this->view($user, $bill);
    }
}
