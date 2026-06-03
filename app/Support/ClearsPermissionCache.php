<?php

namespace App\Support;

use Spatie\Permission\PermissionRegistrar;

trait ClearsPermissionCache
{
    protected function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
