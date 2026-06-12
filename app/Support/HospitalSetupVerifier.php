<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Route as RouteFacade;

class HospitalSetupVerifier
{
    /** @return list<string> */
    public static function frontendPermissions(): array
    {
        $path = resource_path('js/router/index.js');

        if (! is_readable($path)) {
            return [];
        }

        $content = file_get_contents($path);
        preg_match_all("/(?:permission|permissions|permissionAny):[^,\n}]+/", $content, $segments);

        $permissions = [];

        foreach ($segments[0] as $segment) {
            preg_match_all("/['\"]([^'\"]+)['\"]/", $segment, $matches);
            $permissions = array_merge($permissions, $matches[1] ?? []);
        }

        return array_values(array_unique($permissions));
    }

    /** @return list<string> */
    public static function routeMiddlewarePermissions(): array
    {
        $strings = [];

        foreach (RouteFacade::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                $strings = array_merge(
                    $strings,
                    self::middlewareValues($middleware, 'permission:'),
                    self::middlewareValues($middleware, 'role_or_permission:'),
                );
            }
        }

        return array_values(array_unique($strings));
    }

    /** @return list<string> */
    protected static function middlewareValues(string $middleware, string $prefix): array
    {
        if (! str_starts_with($middleware, $prefix)) {
            return [];
        }

        return self::splitMiddlewareValues(substr($middleware, strlen($prefix)));
    }

    /** @return list<string> */
    protected static function splitMiddlewareValues(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode('|', $value))));
    }

    /**
     * @param  list<string>  $strings
     * @return list<string>
     */
    public static function missingMiddlewarePermissions(array $strings): array
    {
        $roleNames = Role::query()->pluck('name')->all();
        $permissionNames = Permission::query()->pluck('name')->all();
        $missing = [];

        foreach ($strings as $string) {
            if (in_array($string, $roleNames, true)) {
                continue;
            }

            if (! in_array($string, $permissionNames, true)) {
                $missing[] = $string;
            }
        }

        return array_values(array_unique($missing));
    }

    /** @return list<string> */
    public static function missingFrontendPermissions(): array
    {
        $permissionNames = Permission::query()->pluck('name')->all();
        $missing = [];

        foreach (self::frontendPermissions() as $permission) {
            if (! in_array($permission, $permissionNames, true)) {
                $missing[] = $permission;
            }
        }

        return $missing;
    }
}
