<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionCheck
{
    /** @var list<string>|null */
    protected static ?array $definedPermissions = null;

    public static function can(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (! self::isDefined($permission)) {
            return false;
        }

        return $user->can($permission);
    }

    /** @param  list<string>  $permissions */
    public static function canAny(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    protected static function isDefined(string $permission): bool
    {
        self::$definedPermissions ??= Permission::query()->pluck('name')->all();

        return in_array($permission, self::$definedPermissions, true);
    }
}
