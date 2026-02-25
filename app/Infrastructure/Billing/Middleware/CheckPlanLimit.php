<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Middleware;

use App\Application\Billing\DTOs\CheckPlanLimitInput;
use App\Application\Billing\UseCases\CheckPlanLimitUseCase;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckPlanLimit
{
    public function __construct(
        private readonly CheckPlanLimitUseCase $checkPlanLimit,
    ) {}

    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $organizationId = $request->attributes->get('auth_organization_id');

        if ($organizationId === null) {
            return $next($request);
        }

        $withinLimit = $this->checkPlanLimit->execute(new CheckPlanLimitInput(
            organizationId: $organizationId,
            resourceType: $resourceType,
        ));

        if (! $withinLimit) {
            return ApiResponse::fail(
                code: 'PLAN_LIMIT_REACHED',
                message: "O limite do plano para '{$resourceType}' foi atingido. Faça upgrade para continuar.",
                status: 402,
            );
        }

        return $next($request);
    }
}
