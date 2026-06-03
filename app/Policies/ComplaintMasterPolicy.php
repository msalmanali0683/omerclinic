<?php

namespace App\Policies;

use App\Models\ComplaintMaster;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintMasterPolicy
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
        return $user->can('view complaint masters');
    }

    public function view(User $user, ComplaintMaster $complaintMaster): bool
    {
        return $user->can('view complaint masters');
    }

    public function create(User $user): bool
    {
        return $user->can('create complaint masters');
    }

    public function update(User $user, ComplaintMaster $complaintMaster): bool
    {
        return $user->can('edit complaint masters');
    }

    public function delete(User $user, ComplaintMaster $complaintMaster): bool
    {
        return $user->can('delete complaint masters');
    }
}
