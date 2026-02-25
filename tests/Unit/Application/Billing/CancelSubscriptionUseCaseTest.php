<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\CancelSubscriptionInput;
use App\Application\Billing\Exceptions\CannotCancelFreePlanException;
use App\Application\Billing\UseCases\CancelSubscriptionUseCase;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Exceptions\SubscriptionAlreadyCanceledException;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('cancels active subscription successfully', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $planId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_stripe_123',
        externalCustomerId: 'cus_stripe_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $plan = Plan::reconstitute(
        id: $planId,
        name: 'Professional',
        slug: 'professional',
        description: 'Professional plan',
        priceMonthly: Money::fromCents(4900),
        priceYearly: Money::fromCents(49900),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 2,
        stripePriceMonthlyId: 'price_monthly_123',
        stripePriceYearlyId: 'price_yearly_123',
        createdAt: $now,
        updatedAt: $now,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);
    $subscriptionRepo->shouldReceive('update')->once();

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $paymentGateway = mock(PaymentGatewayInterface::class);
    $paymentGateway->shouldReceive('cancelSubscription')->with('sub_stripe_123')->once();

    $useCase = new CancelSubscriptionUseCase($subscriptionRepo, $planRepo, $paymentGateway);

    $output = $useCase->execute(new CancelSubscriptionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        reason: 'Too expensive',
        feedback: 'too_expensive',
    ));

    expect($output->cancelAtPeriodEnd)->toBeTrue()
        ->and($output->canceledAt)->not->toBeNull();
});

it('throws CannotCancelFreePlanException for free plan', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $planId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: null,
        externalCustomerId: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $freePlan = Plan::reconstitute(
        id: $planId,
        name: 'Free',
        slug: 'free',
        description: 'Free plan',
        priceMonthly: Money::zero(),
        priceYearly: Money::zero(),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 0,
        stripePriceMonthlyId: null,
        stripePriceYearlyId: null,
        createdAt: $now,
        updatedAt: $now,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($freePlan);

    $paymentGateway = mock(PaymentGatewayInterface::class);

    $useCase = new CancelSubscriptionUseCase($subscriptionRepo, $planRepo, $paymentGateway);

    $useCase->execute(new CancelSubscriptionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
    ));
})->throws(CannotCancelFreePlanException::class);

it('throws SubscriptionAlreadyCanceledException when already canceling', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $planId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month midnight'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        trialEndsAt: null,
        canceledAt: $now,
        cancelAtPeriodEnd: true,
        cancelReason: 'Already canceling',
        cancelFeedback: null,
        externalSubscriptionId: 'sub_stripe_123',
        externalCustomerId: 'cus_stripe_456',
        createdAt: $now,
        updatedAt: $now,
    );

    $plan = Plan::reconstitute(
        id: $planId,
        name: 'Professional',
        slug: 'professional',
        description: 'Professional plan',
        priceMonthly: Money::fromCents(4900),
        priceYearly: Money::fromCents(49900),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 2,
        stripePriceMonthlyId: 'price_monthly_123',
        stripePriceYearlyId: 'price_yearly_123',
        createdAt: $now,
        updatedAt: $now,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $paymentGateway = mock(PaymentGatewayInterface::class);

    $useCase = new CancelSubscriptionUseCase($subscriptionRepo, $planRepo, $paymentGateway);

    $useCase->execute(new CancelSubscriptionInput(
        organizationId: (string) $orgId,
        userId: (string) $userId,
        reason: 'Want to cancel again',
    ));
})->throws(SubscriptionAlreadyCanceledException::class);
