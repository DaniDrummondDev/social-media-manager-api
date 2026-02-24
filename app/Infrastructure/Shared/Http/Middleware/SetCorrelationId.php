<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class SetCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $traceId = (string) Str::uuid();

        $request->headers->set('X-Correlation-ID', $correlationId);
        $request->headers->set('X-Trace-ID', $traceId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Correlation-ID', $correlationId);
        $response->headers->set('X-Trace-ID', $traceId);

        return $response;
    }
}
