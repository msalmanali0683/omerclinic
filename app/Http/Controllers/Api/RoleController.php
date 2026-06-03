<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use App\Support\ClearsPermissionCache;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ClearsPermissionCache;
    public function index(Request $request)
    {
        if (! $request->user()->can('assign roles') && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $roles = Role::with('permissions')->orderBy('name')->get();
        return RoleResource::collection($roles);
    }

    public function store(StoreRoleRequest $request)
    {
        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->permissions);
            $this->clearPermissionCache();
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'role'    => new RoleResource($role->load('permissions')),
        ], 201);
    }

    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role)
    {
        // Prevent renaming the super-admin role
        if ($role->name === 'super-admin') {
            return response()->json(['message' => 'The super-admin role cannot be renamed.'], 422);
        }

        $role->update(['name' => $request->name ?? $role->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions ?? []);
            $this->clearPermissionCache();
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'role'    => new RoleResource($role->fresh('permissions')),
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        // Protect super-admin from deletion
        if ($role->name === 'super-admin') {
            return response()->json(['message' => 'The super-admin role cannot be deleted.'], 422);
        }

        if (! $request->user()->hasRole('super-admin') && ! $request->user()->can('assign roles')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        if (! $request->user()->can('assign permissions') && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions);
        $this->clearPermissionCache();

        return response()->json([
            'message' => 'Role permissions updated.',
            'role'    => new RoleResource($role->fresh('permissions')),
        ]);
    }
}
