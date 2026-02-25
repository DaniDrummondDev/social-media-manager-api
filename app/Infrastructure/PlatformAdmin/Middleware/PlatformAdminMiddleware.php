<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Middleware;

use App\Domain\PlatformAdmin\Repositories\PlatformAdminRepositoryInterface;
use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PlatformAdminMiddleware
{
    public function __construct(
        private readonly PlatformAdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(Request $request, Closure $next, ?string $minRole = null): Response
    {
        $userId = $request->attributes->get('auth_user_id');

        if ($userId === null) {
            return ApiResponse::fail('AUTHENTICATION_ERROR', 'Unauthenticated.', 401);
        }

        $admin = $this->adminRepository->findByUserId(Uuid::fromString($userId));

        if ($admin === null || ! $admin->isActive) {
            return ApiResponse::fail('AUTHORIZATION_ERROR', 'Acesso restrito a administradores da plataforma.', 403);
        }

        if ($minRole !== null) {
            $requiredRole = PlatformRole::from($minRole);

            if (! $admin->role->isAtLeast($requiredRole)) {
                return ApiResponse::fail('INSUFFICIENT_ADMIN_PRIVILEGE', 'Permissão insuficiente para esta ação.', 403);
            }
        }

        $request->attributes->set('auth_admin_id', (string) $admin->id);
        $request->attributes->set('auth_platform_role', $admin->role->value);

        return $next($request);
    }
}
