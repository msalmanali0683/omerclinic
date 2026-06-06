<?php

namespace App\Support;

use App\Models\User;

class AdminAccess
{
    public static function canUpdateUserIdentity(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'hospital-admin']);
    }
}
