<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->can('assign permissions') && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $permissions = Permission::orderBy('name')->get();
        return PermissionResource::collection($permissions);
    }

    public function store(Request $request)
    {
        if (! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Only super-admin can create permissions.'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name, 'guard_name' => 'web']);

        return response()->json([
            'message'    => 'Permission created successfully.',
            'permission' => new PermissionResource($permission),
        ], 201);
    }

    public function update(Request $request, Permission $permission)
    {
        if (! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Only super-admin can edit permissions.'], 403);
        }

        $request->validate([
            'name' => "required|string|max:255|unique:permissions,name,{$permission->id}",
        ]);

        $permission->update(['name' => $request->name]);

        return response()->json([
            'message'    => 'Permission updated successfully.',
            'permission' => new PermissionResource($permission),
        ]);
    }

    public function destroy(Request $request, Permission $permission)
    {
        if (! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Only super-admin can delete permissions.'], 403);
        }

        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully.']);
    }
}
