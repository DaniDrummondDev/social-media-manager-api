<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\SubscriptionOutput;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetSubscriptionUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    public function execute(string $organizationId): SubscriptionOutput
    {
        $subscription = $this->subscriptionRepository->findActiveByOrganization(
            Uuid::fromString($organizationId),
        );

        if ($subscription === null) {
            throw new SubscriptionNotFoundException;
        }

        $plan = $this->planRepository->findById($subscription->planId);

        if ($plan === null) {
            throw new PlanNotFoundException;
        }

        return SubscriptionOutput::fromEntity($subscription, $plan);
    }
}
