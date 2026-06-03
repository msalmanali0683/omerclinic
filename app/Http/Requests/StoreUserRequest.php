<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create users');
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'roles'                 => 'nullable|array',
            'roles.*'               => 'string|exists:roles,name',
            'permissions'           => 'nullable|array',
            'permissions.*'         => 'string|exists:permissions,name',
        ];
    }
}
