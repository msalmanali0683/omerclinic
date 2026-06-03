<?php

namespace App\Support;

use App\Models\User;

class DataEntryOperator
{
    public static function isOnlyDataEntryOperator(User $user): bool
    {
        return $user->hasRole('data-entry-operator') && $user->roles()->count() === 1;
    }

    public static function shouldAutoGenerateToken(User $user): bool
    {
        return $user->can('generate patient tokens');
    }
}
