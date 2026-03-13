<?php

declare(strict_types=1);

use App\Application\Billing\DTOs\CreateCheckoutSessionInput;
use App\Application\Billing\Exceptions\AlreadyOnPlanException;
use App\Application\Billing\Exceptions\CannotCheckoutFreePlanException;
use App\Application\Billing\Exceptions\PlanNotFoundException;
use App\Application\Billing\Exceptions\SubscriptionNotFoundException;
use App\Application\Billing\UseCases\CreateCheckoutSessionUseCase;
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

function createCheckoutTestPlan(bool $isFree = false, ?string $priceId = 'price_123'): Plan
{
    return Plan::reconstitute(
        id: Uuid::generate(),
        name: $isFree ? 'Free' : 'Professional',
        slug: $isFree ? 'free' : 'professional',
        description: 'Test plan',
        priceMonthly: $isFree ? Money::zero() : Money::fromCents(9900),
        priceYearly: $isFree ? Money::zero() : Money::fromCents(99000),
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
        stripePriceMonthlyId: $isFree ? null : $priceId,
        stripePriceYearlyId: $isFree ? null : 'price_annual_123',
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

function createCheckoutTestSubscription(Uuid $planId, ?string $customerId = 'cus_123'): Subscription
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
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: 'sub_123',
        externalCustomerId: $customerId,
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
}

it('creates checkout session successfully', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $freePlan = createCheckoutTestPlan(isFree: true);
    $proPlan = createCheckoutTestPlan(isFree: false);
    $subscription = createCheckoutTestSubscription($freePlan->id);
    $orgId = (string) $subscription->organizationId;

    $planRepo->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($proPlan);

    $subRepo->shouldReceive('findActiveByOrganization')
        ->with(Mockery::on(fn (Uuid $id) => (string) $id === $orgId))
        ->once()
        ->andReturn($subscription);

    $gateway->shouldReceive('createCheckoutSession')
        ->with(
            'cus_123',
            'price_123',
            'https://app.example.com/success',
            'https://app.example.com/cancel',
            Mockery::on(fn (array $meta) => $meta['organization_id'] === $orgId),
            null, // no trial — subscription already has externalSubscriptionId
        )
        ->once()
        ->andReturn([
            'checkout_url' => 'https://checkout.stripe.com/session',
            'session_id' => 'cs_test_123',
            'expires_at' => (new DateTimeImmutable('+1 hour'))->format('c'),
        ]);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway);
    $result = $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        planSlug: 'professional',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));

    expect($result->checkoutUrl)->toBe('https://checkout.stripe.com/session')
        ->and($result->sessionId)->toBe('cs_test_123');
});

it('throws PlanNotFoundException when plan does not exist', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $planRepo->shouldReceive('findBySlug')
        ->with('nonexistent')
        ->once()
        ->andReturn(null);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway);
    $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        planSlug: 'nonexistent',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));
})->throws(PlanNotFoundException::class);

it('throws CannotCheckoutFreePlanException for free plan', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $freePlan = createCheckoutTestPlan(isFree: true);

    $planRepo->shouldReceive('findBySlug')
        ->with('free')
        ->once()
        ->andReturn($freePlan);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway);
    $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
        planSlug: 'free',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));
})->throws(CannotCheckoutFreePlanException::class);

it('throws SubscriptionNotFoundException when no subscription exists', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $proPlan = createCheckoutTestPlan(isFree: false);
    $orgId = (string) Uuid::generate();

    $planRepo->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($proPlan);

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn(null);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway);
    $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        planSlug: 'professional',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));
})->throws(SubscriptionNotFoundException::class);

it('throws AlreadyOnPlanException when already on target plan', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $proPlan = createCheckoutTestPlan(isFree: false);
    $subscription = createCheckoutTestSubscription($proPlan->id); // Already on this plan
    $orgId = (string) $subscription->organizationId;

    $planRepo->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($proPlan);

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway);
    $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        planSlug: 'professional',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));
})->throws(AlreadyOnPlanException::class);

it('applies trial period on first upgrade from free plan', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $freePlan = createCheckoutTestPlan(isFree: true);
    $proPlan = createCheckoutTestPlan(isFree: false);

    // Free subscription — never had Stripe subscription (externalSubscriptionId = null)
    $freeSubscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        planId: $freePlan->id,
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable,
        currentPeriodEnd: new DateTimeImmutable('+30 days'),
        trialEndsAt: null,
        canceledAt: null,
        cancelAtPeriodEnd: false,
        cancelReason: null,
        cancelFeedback: null,
        externalSubscriptionId: null,
        externalCustomerId: 'cus_new_123',
        createdAt: new DateTimeImmutable,
        updatedAt: new DateTimeImmutable,
    );
    $orgId = (string) $freeSubscription->organizationId;

    $planRepo->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($proPlan);

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($freeSubscription);

    $gateway->shouldReceive('createCheckoutSession')
        ->with(
            'cus_new_123',
            'price_123',
            'https://app.example.com/success',
            'https://app.example.com/cancel',
            Mockery::on(fn (array $meta) => $meta['organization_id'] === $orgId),
            14, // trial period applied
        )
        ->once()
        ->andReturn([
            'checkout_url' => 'https://checkout.stripe.com/trial-session',
            'session_id' => 'cs_trial_123',
            'expires_at' => (new DateTimeImmutable('+1 hour'))->format('c'),
        ]);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway, trialPeriodDays: 14);
    $result = $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        planSlug: 'professional',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));

    expect($result->checkoutUrl)->toBe('https://checkout.stripe.com/trial-session')
        ->and($result->sessionId)->toBe('cs_trial_123');
});

it('does not apply trial when organization already had a stripe subscription', function () {
    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $gateway = Mockery::mock(PaymentGatewayInterface::class);

    $creatorPlan = createCheckoutTestPlan(isFree: false, priceId: 'price_creator');
    $agencyPlan = createCheckoutTestPlan(isFree: false, priceId: 'price_agency');

    // Already has externalSubscriptionId — not first time
    $subscription = createCheckoutTestSubscription($creatorPlan->id);
    $orgId = (string) $subscription->organizationId;

    $planRepo->shouldReceive('findBySlug')
        ->with('professional')
        ->once()
        ->andReturn($agencyPlan);

    $subRepo->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $gateway->shouldReceive('createCheckoutSession')
        ->with(
            'cus_123',
            'price_agency',
            'https://app.example.com/success',
            'https://app.example.com/cancel',
            Mockery::on(fn (array $meta) => $meta['organization_id'] === $orgId),
            null, // no trial — already had stripe subscription
        )
        ->once()
        ->andReturn([
            'checkout_url' => 'https://checkout.stripe.com/upgrade',
            'session_id' => 'cs_upgrade_123',
            'expires_at' => (new DateTimeImmutable('+1 hour'))->format('c'),
        ]);

    $useCase = new CreateCheckoutSessionUseCase($planRepo, $subRepo, $gateway, trialPeriodDays: 14);
    $result = $useCase->execute(new CreateCheckoutSessionInput(
        organizationId: $orgId,
        userId: (string) Uuid::generate(),
        planSlug: 'professional',
        billingCycle: 'monthly',
        successUrl: 'https://app.example.com/success',
        cancelUrl: 'https://app.example.com/cancel',
    ));

    expect($result->checkoutUrl)->toBe('https://checkout.stripe.com/upgrade');
});
