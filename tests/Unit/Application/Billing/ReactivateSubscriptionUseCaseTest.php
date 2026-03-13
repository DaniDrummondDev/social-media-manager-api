<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\ReactivateSubscriptionInput;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Application\Billing\UseCases\ReactivateSubscriptionUseCase;
use App\Domain\Billing\Contracts\PaymentGatewayInterface;
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

function createReactivatePlan(): Plan
{
    return Plan::reconstitute(
        id: Uuid::generate(),
        name: 'Professional',
        slug: 'professional',
        description: 'Test plan',
        priceMonthly: Money::fromCents(9900),
        priceYearly: Money::fromCents(99000),
        limits: PlanLimits::fromArray([
            'social_accounts' => 5,
            'active_campaigns' => 10,
            'publications_month' => 100,
            'storage_gb' => 1,
            'members' => 5,
            'ai_generations_month' => 50,
        ]),
        features: PlanFeatures::fromArray([
            'ai_generation_basic' => true,
            'ai_generation_advanced' => false,
            'automations' => true,
        ]),
        isActive: true,
        sortOrder: 1,
        stripePriceMonthlyId: 'price_123',
        stripePriceYearlyId: 'price_annual_123',
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

function createCancelledSubscription(Uuid $planId): Subscription
{
    return Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        planId: $planId,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable,
        currentPeriodEnd: new DateTimeImmutable('+30 days'),
        trialEndsAt: null,
        canceledAt: new DateTimeImmutable('-1 day'),
        cancelAtPeriodEnd: true,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_123',
        externalCustomerId: 'cus_123',
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('reactivates a cancelled subscription successfully', function () {
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $plan = createReactivatePlan();
    $subscription = createCancelledSubscription($plan->id);
    $orgId = (string) $subscription->organizationId;

    $subRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $orgId))
        ->once()
        ->andReturn($subscription);

    $planRepo->shouldReceive('findById')
        ->with(Mockery::on(fn (Uuid $id) => $id->equals($plan->id)))
        ->once()
        ->andReturn($plan);

    $gateway->shouldReceive('reactivateSubscription')
        ->with('sub_123')
        ->once();

    $subRepo->shouldReceive('update')
        ->with(Mockery::on(fn (Subscription $s) => $s->status === SubscriptionStatus::Active
            && $s->canceledAt === null))
        ->once();

    $useCase = new ReactivateSubscriptionUseCase($subRepo, $planRepo, $gateway);
    $result = $useCase->execute(new ReactivateSubscriptionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
    ));

    expect($result->status)->toBe('active')
        ->and($result->plan['name'])->toBe('Professional');
});

it('throws SubscriptionNotFoundException when no subscription exists', function () {
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $orgId = (string) Uuid::generate();

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn(null);

    $useCase = new ReactivateSubscriptionUseCase($subRepo, $planRepo, $gateway);
    $useCase->execute(new ReactivateSubscriptionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
    ));
})->throws(SubscriptionNotFoundException::class);

it('throws PlanNotFoundException when plan does not exist', function () {
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $planId = Uuid::generate();
    $subscription = createCancelledSubscription($planId);
    $orgId = (string) $subscription->organizationId;

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $planRepo->shouldReceive('findById')
        ->once()
        ->andReturn(null);

    $useCase = new ReactivateSubscriptionUseCase($subRepo, $planRepo, $gateway);
    $useCase->execute(new ReactivateSubscriptionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
    ));
})->throws(PlanNotFoundException::class);
