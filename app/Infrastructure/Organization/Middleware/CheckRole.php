<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Middleware;

use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->attributes->get('auth_role');

        if (! in_array($role, $roles, true)) {
            return ApiResponse::fail('AUTHORIZATION_ERROR', 'Insufficient permissions.', 403);
        }

        return $next($request);
    }
}
