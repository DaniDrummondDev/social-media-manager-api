<?php

declare(strict_types=1);

use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Application\Billing\UseCases\DowngradeToFreePlanUseCase;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Shared\ValueObjects\Uuid;

it('expires current subscription and creates free subscription', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $freePlanId = Uuid::generate();
    $subId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: $subId,
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

    $freePlan = Plan::reconstitute(
        id: $freePlanId,
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
    $subscriptionRepo->shouldReceive('findById')->once()->andReturn($subscription);
    $subscriptionRepo->shouldReceive('update')
        ->once()
        ->withArgs(function (Subscription $sub) {
            return $sub->status === SubscriptionStatus::Expired;
        });
    $subscriptionRepo->shouldReceive('create')
        ->once()
        ->withArgs(function (Subscription $sub) use ($freePlanId, $orgId) {
            return $sub->planId->equals($freePlanId)
                && $sub->organizationId->equals($orgId)
                && $sub->status === SubscriptionStatus::Active;
        });

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findFreePlan')->once()->andReturn($freePlan);

    $useCase = new DowngradeToFreePlanUseCase($subscriptionRepo, $planRepo);

    $useCase->execute((string) $subId);
});

it('throws SubscriptionNotFoundException when subscription does not exist', function () {
    $subId = Uuid::generate();

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findById')->once()->andReturn(null);

    $planRepo = mock(PlanRepositoryInterface::class);

    $useCase = new DowngradeToFreePlanUseCase($subscriptionRepo, $planRepo);

    $useCase->execute((string) $subId);
})->throws(SubscriptionNotFoundException::class);

it('throws PlanNotFoundException when free plan does not exist', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subId = Uuid::generate();
    $now = new DateTimeImmutable;

    $subscription = Subscription::reconstitute(
        id: $subId,
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

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findById')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findFreePlan')->once()->andReturn(null);

    $useCase = new DowngradeToFreePlanUseCase($subscriptionRepo, $planRepo);

    $useCase->execute((string) $subId);
})->throws(PlanNotFoundException::class);
