<?php

namespace App\Http\Middleware;

use App\Support\TextCase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CapitalizeRequestText
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)
            && ! $request->is('api/roles*', 'api/permissions*')
        ) {
            $patterns = config('hospital.capitalize_field_patterns', []);

            if ($patterns !== []) {
                $request->replace(
                    TextCase::capitalizeInputArray($request->all(), $patterns)
                );
            }
        }

        return $next($request);
    }
}
