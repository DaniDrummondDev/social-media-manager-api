<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Middleware;

use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InternalOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = (string) config('ai-agents.internal_secret', '');

        if ($expectedSecret === '') {
            return ApiResponse::fail(
                code: 'INTERNAL_ONLY',
                message: 'Internal secret not configured.',
                status: 403,
            );
        }

        $providedSecret = $request->header('X-Internal-Secret', '');

        if (! hash_equals($expectedSecret, (string) $providedSecret)) {
            return ApiResponse::fail(
                code: 'INTERNAL_ONLY',
                message: 'Forbidden.',
                status: 403,
            );
        }

        if (! $this->isInternalIp($request) && ! app()->runningUnitTests()) {
            return ApiResponse::fail(
                code: 'INTERNAL_ONLY',
                message: 'Forbidden.',
                status: 403,
            );
        }

        return $next($request);
    }

    private function isInternalIp(Request $request): bool
    {
        $ip = $request->ip();

        if ($ip === null) {
            return false;
        }

        // Docker internal networks: 172.16.0.0/12, 10.0.0.0/8, 192.168.0.0/16, loopback
        $internalRanges = [
            '172.16.0.0/12',
            '10.0.0.0/8',
            '192.168.0.0/16',
            '127.0.0.0/8',
        ];

        foreach ($internalRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - (int) $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
