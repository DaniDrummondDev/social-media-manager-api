<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\ContentProfileAnalyzerInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
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
use App\Infrastructure\ContentAI\Services\LangGraphTextGenerator;
use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;
use Illuminate\Support\Facades\Http;

function buildPlanAndSubscription(string $planSlug): array
{
    $planId = Uuid::generate();
    $orgId = Uuid::generate();

    $plan = Plan::reconstitute(
        id: $planId,
        name: ucfirst($planSlug),
        slug: $planSlug,
        description: "The {$planSlug} plan",
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

    $subscription = Subscription::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        planId: $planId,
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

    return ['plan' => $plan, 'subscription' => $subscription, 'orgId' => $orgId];
}

beforeEach(function () {
    config([
        'ai-agents.base_url' => 'http://ai-agents:8000',
        'ai-agents.internal_secret' => 'test-secret',
        'ai-agents.poll_interval_ms' => 10,
        'ai-agents.poll_timeout' => 5,
        'ai-agents.plan_access.content_creation' => ['professional', 'agency'],
    ]);

    // PrismTextGeneratorService is final; mock TextGeneratorInterface to use as fallback.
    // Re-wire LangGraphTextGenerator with the mock fallback so plan-gate denial is testable.
    $prismResult = new TextGenerationResult(
        output: ['title' => 'Prism Fallback'],
        tokensInput: 100,
        tokensOutput: 50,
        model: 'gpt-4o-mini',
        durationMs: 1000,
        costEstimate: 0.01,
    );
    $this->fallbackMock = Mockery::mock(TextGeneratorInterface::class);
    $this->fallbackMock->shouldReceive('generateFullContent')->andReturn($prismResult);

    $this->app->bind(TextGeneratorInterface::class, function ($app) {
        return new LangGraphTextGenerator(
            client: $app->make(LangGraphClientInterface::class),
            fallback: $this->fallbackMock,
            planGate: $app->make(AiAgentsPlanGate::class),
        );
    });
});

it('allows professional plan to use content_creation', function () {
    $fixtures = buildPlanAndSubscription('professional');

    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subRepo->shouldReceive('findActiveByOrganization')->andReturn($fixtures['subscription']);
    $this->app->instance(SubscriptionRepositoryInterface::class, $subRepo);

    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->andReturn($fixtures['plan']);
    $this->app->instance(PlanRepositoryInterface::class, $planRepo);

    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordSuccess')->andReturnNull();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'pro-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/pro-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Pro Content'],
            'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
        ]),
    ]);

    request()->attributes->set('auth_organization_id', (string) $fixtures['orgId']);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result->model)->toBe('langgraph_multi_agent');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/pipelines/content-creation'));
});

it('denies free plan from content_creation and falls back to Prism', function () {
    $fixtures = buildPlanAndSubscription('free');

    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subRepo->shouldReceive('findActiveByOrganization')->andReturn($fixtures['subscription']);
    $this->app->instance(SubscriptionRepositoryInterface::class, $subRepo);

    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->andReturn($fixtures['plan']);
    $this->app->instance(PlanRepositoryInterface::class, $planRepo);

    Http::fake();

    request()->attributes->set('auth_organization_id', (string) $fixtures['orgId']);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result->model)->toBe('gpt-4o-mini')
        ->and($result->output)->toBe(['title' => 'Prism Fallback']);
    Http::assertNothingSent();
});

it('allows agency plan to use content_dna pipeline', function () {
    config(['ai-agents.plan_access.content_dna' => ['agency']]);

    $fixtures = buildPlanAndSubscription('agency');

    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subRepo->shouldReceive('findActiveByOrganization')->andReturn($fixtures['subscription']);
    $this->app->instance(SubscriptionRepositoryInterface::class, $subRepo);

    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->andReturn($fixtures['plan']);
    $this->app->instance(PlanRepositoryInterface::class, $planRepo);

    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordSuccess')->andReturnNull();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-dna' => Http::response(['job_id' => 'agency-dna'], 202),
        'http://ai-agents:8000/api/v1/jobs/agency-dna' => Http::response([
            'status' => 'completed',
            'result' => ['profile' => 'agency data'],
            'metadata' => ['total_tokens' => 1000, 'total_cost' => 0.12, 'duration_ms' => 6000],
        ]),
    ]);

    request()->attributes->set('auth_organization_id', (string) $fixtures['orgId']);

    $profiler = app(ContentProfileAnalyzerInterface::class);
    $result = $profiler->analyzeProfile(
        organizationId: (string) $fixtures['orgId'],
        publishedContents: [['text' => 'sample']],
        metrics: [['likes' => 100]],
    );

    expect($result->model)->toBe('langgraph_multi_agent');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/pipelines/content-dna'));
});

// NOTE: content_dna and social_listening adapters do not implement plan gate verification.
// Plan gate is only implemented in LangGraphTextGenerator.
// The following tests are deferred until plan gate is added to those adapters:
// - it('denies professional plan from content_dna pipeline')
// - it('denies professional plan from social_listening pipeline')
