<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->attributes->get('auth_organization_id');

        if ($organizationId && DB::getDriverName() === 'pgsql') {
            DB::statement("SET LOCAL app.current_org_id = ?", [$organizationId]);
        }

        return $next($request);
    }
}
