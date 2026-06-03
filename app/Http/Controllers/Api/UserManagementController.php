<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ClearsPermissionCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    use ClearsPermissionCache;
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles', 'permissions');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        $users = $query->latest()->paginate($request->get('per_page', 15));

        return UserResource::collection($users);
    }

    public function doctors(Request $request)
    {
        $doctors = User::role('doctor')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $doctors->map(fn (User $user) => [
                'id'   => $user->id,
                'name' => $user->name,
            ])->values(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->filled('roles')) {
            // Prevent assigning super-admin unless requesting user IS super-admin
            $roles = $request->roles;
            if (in_array('super-admin', $roles) && ! $request->user()->hasRole('super-admin')) {
                $roles = array_filter($roles, fn($r) => $r !== 'super-admin');
            }
            $user->syncRoles($roles);
        }

        if ($request->filled('permissions')) {
            $user->syncPermissions($request->permissions);
            $this->clearPermissionCache();
        }

        return response()->json([
            'message' => 'User created successfully.',
            'user'    => new UserResource($user->load(['roles', 'permissions'])),
        ], 201);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        return new UserResource($user->load('roles', 'permissions'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        // Prevent non-super-admin from editing a super-admin
        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'You cannot edit a super-admin user.'], 403);
        }

        $data = $request->only(['name', 'email']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $roles = $request->roles ?? [];
            if (in_array('super-admin', $roles) && ! $request->user()->hasRole('super-admin')) {
                $roles = array_filter($roles, fn($r) => $r !== 'super-admin');
            }
            $user->syncRoles($roles);
            $this->clearPermissionCache();
        }

        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions ?? []);
            $this->clearPermissionCache();
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user'    => new UserResource($user->fresh(['roles', 'permissions'])),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        // Prevent self-deletion
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        // Protect super-admin users from being deleted by non-super-admins
        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'You cannot delete a super-admin user.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    /**
     * Sync roles for a user.
     */
    public function syncRoles(Request $request, User $user)
    {
        $this->authorize('assignRoles', $user);

        $request->validate([
            'roles'   => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $roles = $request->roles;

        // Protect against removing super-admin role from self
        if ($user->id === $request->user()->id && ! in_array('super-admin', $roles) && $user->hasRole('super-admin')) {
            return response()->json(['message' => 'You cannot remove your own super-admin role.'], 422);
        }

        // Prevent assigning super-admin unless requesting user IS super-admin
        if (in_array('super-admin', $roles) && ! $request->user()->hasRole('super-admin')) {
            return response()->json(['message' => 'Only a super-admin can assign the super-admin role.'], 403);
        }

        $user->syncRoles($roles);
        $this->clearPermissionCache();

        return response()->json([
            'message' => 'Roles updated successfully.',
            'user'    => new UserResource($user->fresh(['roles', 'permissions'])),
        ]);
    }

    /**
     * Sync direct permissions for a user.
     */
    public function syncPermissions(Request $request, User $user)
    {
        if (! $request->user()->can('assign permissions')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->syncPermissions($request->permissions);
        $this->clearPermissionCache();

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'user'    => new UserResource($user->fresh(['roles', 'permissions'])),
        ]);
    }
}
