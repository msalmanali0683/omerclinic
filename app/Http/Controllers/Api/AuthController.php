<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle SPA login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return response()->json([
            'message' => 'Logged in successfully.',
            ...$this->authPayload(Auth::user()),
        ]);
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user with roles and permissions.
     */
    public function me(Request $request)
    {
        return response()->json($this->authPayload($request->user()));
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (! AdminAccess::canUpdateUserIdentity($user) && $request->hasAny(['name', 'email'])) {
            return response()->json([
                'message' => 'Only administrators can update name and email.',
            ], 403);
        }

        $rules = [
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ];

        if (AdminAccess::canUpdateUserIdentity($user)) {
            $rules['name'] = 'sometimes|required|string|max:255';
            $rules['email'] = 'sometimes|required|email|unique:users,email,'.$user->id;
        }

        $validated = $request->validate($rules);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            ...$this->authPayload($user->fresh()),
        ]);
    }

    protected function authPayload($user): array
    {
        $user->loadMissing(['roles', 'permissions']);

        return [
            'user'        => new UserResource($user),
            'roles'       => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }
}
