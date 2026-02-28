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
use App\Infrastructure\Billing\Middleware\CheckPlanFeature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->subscriptionRepository = Mockery::mock(SubscriptionRepositoryInterface::class);
    $this->planRepository = Mockery::mock(PlanRepositoryInterface::class);

    $this->middleware = new CheckPlanFeature(
        $this->subscriptionRepository,
        $this->planRepository,
    );

    $this->orgId = '550e8400-e29b-41d4-a716-446655440000';
    $this->planId = '660e8400-e29b-41d4-a716-446655440001';
});

afterEach(function () {
    Mockery::close();
});

it('passes through when no organization context', function () {
    $request = Request::create('/api/v1/crm/connections', 'GET');
    // No auth_organization_id attribute set

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'crm_native');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('OK');
});

it('returns 402 when no active subscription exists', function () {
    $request = Request::create('/api/v1/crm/connections', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn(null);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'crm_native');

    expect($response->getStatusCode())->toBe(402);

    $content = json_decode($response->getContent(), true);
    expect($content['errors'][0]['code'])->toBe('FEATURE_NOT_AVAILABLE');
});

it('returns 402 when plan not found', function () {
    $request = Request::create('/api/v1/crm/connections', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn(null);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'crm_native');

    expect($response->getStatusCode())->toBe(402);
});

it('returns 402 when feature is not enabled in plan', function () {
    $request = Request::create('/api/v1/crm/connections', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, ['crm_native' => false]);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'crm_native');

    expect($response->getStatusCode())->toBe(402);

    $content = json_decode($response->getContent(), true);
    expect($content['errors'][0]['code'])->toBe('FEATURE_NOT_AVAILABLE')
        ->and($content['errors'][0]['message'])->toContain('crm_native');
});

it('passes through when feature is enabled in plan', function () {
    $request = Request::create('/api/v1/crm/connections', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, ['crm_native' => true]);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'crm_native');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('OK');
});

it('checks ai_intelligence feature correctly', function () {
    $request = Request::create('/api/v1/ai-intelligence/best-times', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, ['ai_intelligence' => true]);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'ai_intelligence');

    expect($response->getStatusCode())->toBe(200);
});

it('checks ai_learning feature correctly', function () {
    $request = Request::create('/api/v1/ai/feedback', 'POST');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, ['ai_learning' => false]);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'ai_learning');

    expect($response->getStatusCode())->toBe(402);
});

it('checks ai_generation_advanced feature correctly', function () {
    $request = Request::create('/api/v1/ai/adapt-content', 'POST');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, ['ai_generation_advanced' => true]);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'ai_generation_advanced');

    expect($response->getStatusCode())->toBe(200);
});

it('returns 402 for unknown feature key', function () {
    $request = Request::create('/api/v1/some-route', 'GET');
    $request->attributes->set('auth_organization_id', $this->orgId);

    $subscription = createCheckPlanFeatureSubscription($this->orgId, $this->planId);
    $plan = createCheckPlanFeaturePlan($this->planId, []);

    $this->subscriptionRepository
        ->shouldReceive('findActiveByOrganization')
        ->once()
        ->andReturn($subscription);

    $this->planRepository
        ->shouldReceive('findById')
        ->once()
        ->andReturn($plan);

    $next = fn () => new Response('OK', 200);

    $response = $this->middleware->handle($request, $next, 'unknown_feature');

    expect($response->getStatusCode())->toBe(402);
});

/**
 * Helper to create mock subscription for CheckPlanFeature tests.
 */
function createCheckPlanFeatureSubscription(string $orgId, string $planId): Subscription
{
    return Subscription::reconstitute(
        id: Uuid::fromString('770e8400-e29b-41d4-a716-446655440002'),
        organizationId: Uuid::fromString($orgId),
        planId: Uuid::fromString($planId),
        status: SubscriptionStatus::Active,
        billingCycle: BillingCycle::Monthly,
        currentPeriodStart: new DateTimeImmutable('first day of this month'),
        currentPeriodEnd: new DateTimeImmutable('last day of this month'),
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

/**
 * Helper to create mock plan for CheckPlanFeature tests.
 *
 * @param  array<string, bool>  $features
 */
function createCheckPlanFeaturePlan(string $planId, array $features): Plan
{
    $defaultFeatures = [
        'ai_generation_basic' => true,
        'ai_generation_advanced' => false,
        'ai_intelligence' => false,
        'ai_learning' => false,
        'automations' => false,
        'webhooks' => false,
        'crm_native' => false,
        'export_pdf' => false,
        'export_csv' => true,
        'priority_publishing' => false,
    ];

    $mergedFeatures = array_merge($defaultFeatures, $features);

    return Plan::reconstitute(
        id: Uuid::fromString($planId),
        name: 'Test Plan',
        slug: 'test-plan',
        description: 'Test plan description',
        priceMonthly: Money::zero(),
        priceYearly: Money::zero(),
        limits: PlanLimits::fromArray([]),
        features: PlanFeatures::fromArray($mergedFeatures),
        isActive: true,
        sortOrder: 1,
        stripePriceMonthlyId: null,
        stripePriceYearlyId: null,
        createdAt: new DateTimeImmutable(),
        updatedAt: new DateTimeImmutable(),
    );
}
