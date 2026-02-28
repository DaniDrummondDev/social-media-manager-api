<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Middleware;

use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckPlanFeature
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $organizationId = $request->attributes->get('auth_organization_id');

        if ($organizationId === null) {
            return $next($request);
        }

        $orgId = Uuid::fromString($organizationId);
        $subscription = $this->subscriptionRepository->findActiveByOrganization($orgId);

        if ($subscription === null) {
            return ApiResponse::fail(
                code: 'FEATURE_NOT_AVAILABLE',
                message: "A feature '{$featureKey}' não está disponível no seu plano. Faça upgrade para acessar.",
                status: 402,
            );
        }

        $plan = $this->planRepository->findById($subscription->planId);

        if ($plan === null) {
            return ApiResponse::fail(
                code: 'FEATURE_NOT_AVAILABLE',
                message: "A feature '{$featureKey}' não está disponível no seu plano. Faça upgrade para acessar.",
                status: 402,
            );
        }

        if (! $plan->features->hasFeature($featureKey)) {
            return ApiResponse::fail(
                code: 'FEATURE_NOT_AVAILABLE',
                message: "A feature '{$featureKey}' não está disponível no seu plano. Faça upgrade para acessar.",
                status: 402,
            );
        }

        return $next($request);
    }
}
