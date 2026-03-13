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
            $safeId = preg_replace('/[^a-f0-9\-]/', '', (string) $organizationId);
            DB::unprepared("SET LOCAL app.current_org_id = '{$safeId}'");
        }

        return $next($request);
    }
}
