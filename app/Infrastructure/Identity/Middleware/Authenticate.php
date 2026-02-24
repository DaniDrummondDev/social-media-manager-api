<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Middleware;

use App\Application\Identity\Contracts\AuthTokenServiceInterface;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class Authenticate
{
    public function __construct(
        private readonly AuthTokenServiceInterface $authTokenService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return ApiResponse::fail('AUTHENTICATION_ERROR', 'Unauthenticated.', 401);
        }

        $payload = $this->authTokenService->validateAccessToken($token);

        if ($payload === null) {
            return ApiResponse::fail('AUTHENTICATION_ERROR', 'Unauthenticated.', 401);
        }

        $request->attributes->set('auth_user_id', $payload['sub']);
        $request->attributes->set('auth_organization_id', $payload['org']);
        $request->attributes->set('auth_email', $payload['email']);
        $request->attributes->set('auth_role', $payload['role']);
        $request->attributes->set('auth_jti', $payload['jti']);

        return $next($request);
    }
}
