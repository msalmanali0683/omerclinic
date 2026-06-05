<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'email'              => $this->email,
            'roles'                  => $this->roles->pluck('name')->values(),
            'permissions'            => $this->getAllPermissions()->pluck('name')->values(),
            'direct_permissions'     => $this->getDirectPermissions()->pluck('name')->values(),
            'inherited_permissions'  => $this->getPermissionsViaRoles()->pluck('name')->unique()->values(),
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
