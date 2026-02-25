<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class DowngradeToFreePlanUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
    ) {}

    public function execute(string $subscriptionId): void
    {
        $subscription = $this->subscriptionRepository->findById(Uuid::fromString($subscriptionId));
        if ($subscription === null) {
            throw new SubscriptionNotFoundException;
        }

        $freePlan = $this->planRepository->findFreePlan();
        if ($freePlan === null) {
            throw new PlanNotFoundException('Plano gratuito não encontrado.');
        }

        $expired = $subscription->expire();
        $this->subscriptionRepository->update($expired);

        $freeSubscription = Subscription::createFree(
            $subscription->organizationId,
            $freePlan->id,
            'system',
        );

        $this->subscriptionRepository->create($freeSubscription);
    }
}
