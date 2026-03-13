<?php

declare(strict_types=1);

namespace App\Application\Billing\UseCases;

use App\Application\Billing\DTOs\CheckoutSessionOutput;
use App\Application\Billing\DTOs\CreateCheckoutSessionInput;
use App\Application\Billing\Exceptions\AlreadyOnPlanException;
use App\Application\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Shared\ValueObjects\Uuid;

final class CreateCheckoutSessionUseCase
{
    public function __construct(
        private readonly PlanRepositoryInterface $planRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentGatewayInterface $paymentGateway,
        private readonly ?int $trialPeriodDays = null,
    ) {}

    public function execute(CreateCheckoutSessionInput $input): CheckoutSessionOutput
    {
        $plan = $this->planRepository->findBySlug($input->planSlug);
        if ($plan === null || ! $plan->isActive) {
            throw new PlanNotFoundException;
        }

        if ($plan->isFree()) {
            throw new CannotCheckoutFreePlanException;
        }

        $orgId = Uuid::fromString($input->organizationId);
        $subscription = $this->subscriptionRepository->findActiveByOrganization($orgId);
        if ($subscription === null) {
            throw new SubscriptionNotFoundException;
        }

        if ($subscription->planId->equals($plan->id)) {
            throw new AlreadyOnPlanException;
        }

        $cycle = BillingCycle::from($input->billingCycle);
        $priceId = $plan->getStripePriceId($cycle);

        if ($priceId === null || $priceId === '') {
            throw new \RuntimeException('Plan has no Stripe price configured for this billing cycle.');
        }

        $customerId = $subscription->externalCustomerId ?? '';

        if ($customerId === '') {
            throw new \RuntimeException('Organization has no Stripe customer ID.');
        }

        $trialDays = $this->shouldApplyTrial($subscription)
            ? $this->trialPeriodDays
            : null;

        $session = $this->paymentGateway->createCheckoutSession(
            customerId: $customerId,
            priceId: $priceId,
            successUrl: $input->successUrl,
            cancelUrl: $input->cancelUrl,
            metadata: [
                'organization_id' => $input->organizationId,
                'plan_id' => (string) $plan->id,
            ],
            trialPeriodDays: $trialDays,
        );

        return new CheckoutSessionOutput(
            checkoutUrl: $session['checkout_url'],
            sessionId: $session['session_id'],
            expiresAt: $session['expires_at'],
        );
    }

    /**
     * Trial applies only on first paid subscription (upgrade from Free).
     * If the organization already had a Stripe subscription, no trial.
     */
    private function shouldApplyTrial(Subscription $subscription): bool
    {
        if ($this->trialPeriodDays === null || $this->trialPeriodDays <= 0) {
            return false;
        }

        return $subscription->externalSubscriptionId === null;
    }
}
