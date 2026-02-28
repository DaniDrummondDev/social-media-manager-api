<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

class AiAgentsPlanGate
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepo,
        private readonly PlanRepositoryInterface $planRepo,
    ) {}

    public function canAccess(string $organizationId, string $pipeline): bool
    {
        $subscription = $this->subscriptionRepo->findActiveByOrganization(
            Uuid::fromString($organizationId),
        );

        if ($subscription === null) {
            return false;
        }

        $plan = $this->planRepo->findById($subscription->planId);

        if ($plan === null) {
            return false;
        }

        /** @var array<string> $allowed */
        $allowed = config("ai-agents.plan_access.{$pipeline}", []);

        return in_array($plan->slug, $allowed, true);
    }
}
