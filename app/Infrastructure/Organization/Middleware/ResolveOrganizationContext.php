<?php

declare(strict_types=1);

namespace App\Infrastructure\Organization\Middleware;

use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveOrganizationContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->attributes->get('auth_organization_id');

        if (empty($organizationId)) {
            return ApiResponse::fail(
                'AUTHORIZATION_ERROR',
                'No organization selected. Use POST /organizations/switch to select an organization.',
                403,
            );
        }

        return $next($request);
    }
}
