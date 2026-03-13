<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\ContentProfileAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\MentionAnalyzerInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\Contracts\VisualAdapterInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;
use App\Infrastructure\Shared\Exceptions\AiAgentsRequestException;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;
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

beforeEach(function () {
    config([
        'ai-agents.base_url' => 'http://ai-agents:8000',
        'ai-agents.internal_secret' => 'test-secret',
        'ai-agents.poll_interval_ms' => 10,
        'ai-agents.poll_timeout' => 1,
    ]);

    $planId = Uuid::generate();
    $orgId = Uuid::generate();
    $this->orgId = $orgId;

    $plan = Plan::reconstitute(
        id: $planId,
        name: 'Professional',
        slug: 'professional',
        description: 'Pro plan',
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

    $subRepo = Mockery::mock(SubscriptionRepositoryInterface::class);
    $subRepo->shouldReceive('findActiveByOrganization')->andReturn($subscription);
    $this->app->instance(SubscriptionRepositoryInterface::class, $subRepo);

    $planRepo = Mockery::mock(PlanRepositoryInterface::class);
    $planRepo->shouldReceive('findById')->andReturn($plan);
    $this->app->instance(PlanRepositoryInterface::class, $planRepo);

    config(['ai-agents.plan_access.content_creation' => ['professional', 'agency']]);

    request()->attributes->set('auth_organization_id', (string) $orgId);

    // Build a fallback result to return from the TextGeneratorInterface mock
    $this->prismResult = new TextGenerationResult(
        output: ['title' => 'Prism Fallback Title'],
        tokensInput: 100,
        tokensOutput: 50,
        model: 'gpt-4o-mini',
        durationMs: 1500,
        costEstimate: 0.01,
    );

    // PrismTextGeneratorService is final; mock the interface it implements instead.
    // Override the DI binding so LangGraphTextGenerator receives the mock as fallback.
    $this->fallbackMock = Mockery::mock(TextGeneratorInterface::class);
    $this->fallbackMock->shouldReceive('generateFullContent')->andReturn($this->prismResult);
    $this->fallbackMock->shouldReceive('generateTitle')->andReturn($this->prismResult);

    $this->app->bind(TextGeneratorInterface::class, function ($app) {
        return new LangGraphTextGenerator(
            client: $app->make(LangGraphClientInterface::class),
            fallback: $this->fallbackMock,
            planGate: $app->make(AiAgentsPlanGate::class),
        );
    });
});

it('falls back to Prism when ai-agents returns 500', function () {
    // Circuit breaker must allow the attempt (isOpen = false) but record failure
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordFailure')->once();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'AI Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result->model)->not->toBe('langgraph_multi_agent')
        ->and($result->output)->toBe(['title' => 'Prism Fallback Title']);
});

it('falls back to Prism when ai-agents times out during polling', function () {
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordFailure')->once();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'timeout-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/timeout-job' => Http::response(['status' => 'running']),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'AI Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result->model)->toBe('gpt-4o-mini')
        ->and($result->output)->toBe(['title' => 'Prism Fallback Title']);
});

it('falls back to Prism when circuit is already open', function () {
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->with('content_creation')->andReturnTrue();
    $circuitBreaker->shouldNotReceive('recordFailure');
    $circuitBreaker->shouldNotReceive('recordSuccess');
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake();

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'AI Marketing',
        socialNetworks: ['instagram_feed'],
    );

    expect($result->model)->toBe('gpt-4o-mini');
    Http::assertNothingSent();
});

it('throws when content_dna pipeline fails with 500 and no fallback', function () {
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordFailure')->once();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-dna' => Http::response(['error' => 'Internal Server Error'], 500),
    ]);

    $profiler = app(ContentProfileAnalyzerInterface::class);
    $profiler->analyzeProfile(
        organizationId: (string) $this->orgId,
        publishedContents: [['text' => 'sample']],
        metrics: [['likes' => 100]],
    );
})->throws(AiAgentsRequestException::class);

it('throws when social_listening pipeline times out and no fallback', function () {
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordFailure')->once();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/social-listening' => Http::response(['job_id' => 'timeout-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/timeout-job' => Http::response(['status' => 'running']),
    ]);

    $analyzer = app(MentionAnalyzerInterface::class);
    $analyzer->analyzeMention(
        organizationId: (string) $this->orgId,
        mention: ['text' => 'Test mention'],
        brandContext: ['name' => 'TestBrand'],
    );
})->throws(AiAgentsTimeoutException::class);

it('throws when visual_adaptation circuit is open and no fallback', function () {
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->with('visual_adaptation')->andReturnTrue();
    $circuitBreaker->shouldNotReceive('recordFailure');
    $circuitBreaker->shouldNotReceive('recordSuccess');
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    Http::fake();

    $adapter = app(VisualAdapterInterface::class);
    $adapter->adaptImage(
        organizationId: (string) $this->orgId,
        imageUrl: 'https://example.com/image.jpg',
        targetNetworks: ['instagram_feed'],
    );
})->throws(AiAgentsCircuitOpenException::class);
