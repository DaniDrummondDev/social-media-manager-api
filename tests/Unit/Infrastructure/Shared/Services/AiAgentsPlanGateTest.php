<?php

declare(strict_types=1);

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
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->subscriptionRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $this->planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $this->gate = new AiAgentsPlanGate($this->subscriptionRepo, $this->planRepo);
});

function createMockPlan(string $slug): Plan
{
    return Plan::reconstitute(
        id: Uuid::generate(),
        name: ucfirst($slug),
        slug: $slug,
        description: "The {$slug} plan",
        priceMonthly: Money::fromCents(4900),
        priceYearly: Money::fromCents(47000),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray([]),
        isActive: true,
        sortOrder: 1,
        stripePriceMonthlyId: null,
        stripePriceYearlyId: null,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
    );
}

function createMockSubscription(Uuid $planId, Uuid $orgId): Subscription
{
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
        externalSubscriptionId: null,
        externalCustomerId: null,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
    );
}

it('returns true for allowed plan', function () {
    $orgId = Uuid::generate();
    $plan = createMockPlan('professional');
    $subscription = createMockSubscription($plan->id, $orgId);

    config(['ai-agents.plan_access.content_creation' => ['professional', 'agency']]);

    $this->subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === (string) $orgId))
        ->once()
        ->andReturn($subscription);

    $this->planRepo->shouldReceive('findById')
        ->with($subscription->planId)
        ->once()
        ->andReturn($plan);

    expect($this->gate->canAccess((string) $orgId, 'content_creation'))->toBeTrue();
});

it('returns false for disallowed plan', function () {
    $orgId = Uuid::generate();
    $plan = createMockPlan('free');
    $subscription = createMockSubscription($plan->id, $orgId);

    config(['ai-agents.plan_access.content_creation' => ['professional', 'agency']]);

    $this->subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === (string) $orgId))
        ->once()
        ->andReturn($subscription);

    $this->planRepo->shouldReceive('findById')
        ->with($subscription->planId)
        ->once()
        ->andReturn($plan);

    expect($this->gate->canAccess((string) $orgId, 'content_creation'))->toBeFalse();
});

it('returns false when no active subscription found', function () {
    $orgId = Uuid::generate();

    $this->subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturnNull();

    $this->planRepo->shouldNotReceive('findById');

    expect($this->gate->canAccess((string) $orgId, 'content_creation'))->toBeFalse();
});

it('returns false when plan not found', function () {
    $orgId = Uuid::generate();
    $planId = Uuid::generate();
    $subscription = createMockSubscription($planId, $orgId);

    $this->subscriptionRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepo->shouldReceive('findById')
        ->with($planId)
        ->once()
        ->andReturnNull();

    expect($this->gate->canAccess((string) $orgId, 'content_creation'))->toBeFalse();
});
