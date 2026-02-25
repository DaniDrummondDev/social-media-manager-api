<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\CancelSubscriptionInput;
use App\Application\Billing\DTOs\SubscriptionOutput;
use App\Application\Billing\Exceptions\CannotCancelFreePlanException;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\CancelFeedback;
use App\Domain\Shared\ValueObjects\Uuid;

final class CancelSubscriptionUseCase
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
    ) {}

    public function execute(CancelSubscriptionInput $input): SubscriptionOutput
    {
        $orgId = Uuid::fromString($input->organizationId);

        $subscription = $this->subscriptionRepository->findActiveByOrganization($orgId);
        if ($subscription === null) {
            throw new SubscriptionNotFoundException;
        }

        $plan = $this->planRepository->findById($subscription->planId);
        if ($plan === null) {
            throw new PlanNotFoundException;
        }

        if ($plan->isFree()) {
            throw new CannotCancelFreePlanException;
        }

        $feedback = $input->feedback !== null ? CancelFeedback::from($input->feedback) : null;
        $subscription = $subscription->cancel($input->reason, $feedback);

        if ($subscription->externalSubscriptionId !== null) {
            $this->paymentGateway->cancelSubscription($subscription->externalSubscriptionId);
        }

        $this->subscriptionRepository->update($subscription);

        return SubscriptionOutput::fromEntity($subscription, $plan);
    }
}
