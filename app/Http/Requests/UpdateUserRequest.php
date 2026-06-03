<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit users');
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'name'        => 'sometimes|required|string|max:255',
            'email'       => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'    => 'sometimes|nullable|string|min:8|confirmed',
            'roles'       => 'nullable|array',
            'roles.*'     => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }
}
