<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\CheckPlanLimitInput;
use App\Application\Billing\UseCases\CheckPlanLimitUseCase;
use App\Domain\Billing\Entities\Plan;
use App\Domain\Billing\Entities\Subscription;
use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\BillingCycle;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Billing\ValueObjects\PlanFeatures;
use App\Domain\Billing\ValueObjects\PlanLimits;
use App\Domain\Billing\ValueObjects\SubscriptionStatus;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;

function createPlanLimitTestPlan(Uuid $planId, array $limitsData = []): Plan
{
    $now = new DateTimeImmutable;

    return Plan::reconstitute(
        id: $planId,
        name: 'Professional',
        slug: 'professional',
        description: 'Professional plan',
        priceMonthly: Money::fromCents(4900),
        priceYearly: Money::fromCents(49900),
        limits: PlanLimits::fromArray($limitsData),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 2,
        stripePriceMonthlyId: 'price_monthly_123',
        stripePriceYearlyId: 'price_yearly_123',
        createdAt: $now,
        updatedAt: $now,
    );
}

function createPlanLimitTestSubscription(Uuid $orgId, Uuid $planId): Subscription
{
    $now = new DateTimeImmutable;

    return Subscription::reconstitute(
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
}

it('returns true when usage is within limit', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createPlanLimitTestSubscription($orgId, $planId);
    $plan = createPlanLimitTestPlan($planId, ['publications_month' => 30]);

    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $usageRecord = UsageRecord::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        resourceType: UsageResourceType::Publications,
        quantity: 10,
        periodStart: $periodStart,
        periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        recordedAt: new DateTimeImmutable,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')->once()->andReturn($usageRecord);

    $useCase = new CheckPlanLimitUseCase($subscriptionRepo, $planRepo, $usageRepo);

    $result = $useCase->execute(new CheckPlanLimitInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
    ));

    expect($result)->toBeTrue();
});

it('returns false when usage is at limit (not exceeded but equal)', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createPlanLimitTestSubscription($orgId, $planId);
    $plan = createPlanLimitTestPlan($planId, ['publications_month' => 30]);

    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $usageRecord = UsageRecord::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        resourceType: UsageResourceType::Publications,
        quantity: 30,
        periodStart: $periodStart,
        periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        recordedAt: new DateTimeImmutable,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')->once()->andReturn($usageRecord);

    $useCase = new CheckPlanLimitUseCase($subscriptionRepo, $planRepo, $usageRepo);

    $result = $useCase->execute(new CheckPlanLimitInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
    ));

    expect($result)->toBeFalse();
});

it('returns true when limit is unlimited (-1)', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createPlanLimitTestSubscription($orgId, $planId);
    $plan = createPlanLimitTestPlan($planId, ['publications_month' => -1]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldNotReceive('findByOrganizationAndResource');

    $useCase = new CheckPlanLimitUseCase($subscriptionRepo, $planRepo, $usageRepo);

    $result = $useCase->execute(new CheckPlanLimitInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
    ));

    expect($result)->toBeTrue();
});

it('returns true when no usage record exists (quantity 0)', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createPlanLimitTestSubscription($orgId, $planId);
    $plan = createPlanLimitTestPlan($planId, ['publications_month' => 30]);

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')->once()->andReturn(null);

    $useCase = new CheckPlanLimitUseCase($subscriptionRepo, $planRepo, $usageRepo);

    $result = $useCase->execute(new CheckPlanLimitInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
    ));

    expect($result)->toBeTrue();
});

it('returns false when usage exceeds limit', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createPlanLimitTestSubscription($orgId, $planId);
    $plan = createPlanLimitTestPlan($planId, ['publications_month' => 30]);

    $periodStart = new DateTimeImmutable('first day of this month midnight');
    $usageRecord = UsageRecord::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        resourceType: UsageResourceType::Publications,
        quantity: 35,
        periodStart: $periodStart,
        periodEnd: new DateTimeImmutable('last day of this month 23:59:59'),
        recordedAt: new DateTimeImmutable,
    );

    $subscriptionRepo = mock(SubscriptionRepositoryInterface::class);
    $subscriptionRepo->shouldReceive('findActiveByOrganization')->once()->andReturn($subscription);

    $planRepo = mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->with($planId)->once()->andReturn($plan);

    $usageRepo = mock(UsageRecordRepositoryInterface::class);
    $usageRepo->shouldReceive('findByOrganizationAndResource')->once()->andReturn($usageRecord);

    $useCase = new CheckPlanLimitUseCase($subscriptionRepo, $planRepo, $usageRepo);

    $result = $useCase->execute(new CheckPlanLimitInput(
        organizationId: (string) $orgId,
        resourceType: 'publications',
    ));

    expect($result)->toBeFalse();
});
