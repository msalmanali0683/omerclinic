<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name'       => 'record vitals on patient registration',
            'guard_name' => 'web',
        ]);

        $roles = Role::whereIn('name', [
            'doctor',
            'nurse',
            'receptionist',
            'data-entry-operator',
            'hospital-admin',
            'super-admin',
        ])->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'record vitals on patient registration')->first();

        if ($permission) {
            $permission->delete();
        }
    }
};
