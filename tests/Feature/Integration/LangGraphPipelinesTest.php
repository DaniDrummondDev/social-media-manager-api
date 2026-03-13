<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\ContentProfileAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\MentionAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\ContentProfileResult;
use App\Application\AIIntelligence\DTOs\MentionAnalysisResult;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\Contracts\VisualAdapterInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Application\ContentAI\DTOs\VisualAdaptationResult;
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
use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'ai-agents.base_url' => 'http://ai-agents:8000',
        'ai-agents.internal_secret' => 'test-secret',
        'ai-agents.poll_interval_ms' => 10,
        'ai-agents.poll_timeout' => 5,
    ]);

    // Mock circuit breaker to always allow (isOpen = false)
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordSuccess')->andReturnNull();
    $circuitBreaker->shouldReceive('recordFailure')->andReturnNull();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    $planId = Uuid::generate();
    $orgId = Uuid::generate();
    $this->orgIdStr = (string) $orgId;

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

    config([
        'ai-agents.plan_access.content_creation' => ['professional', 'agency'],
        'ai-agents.plan_access.visual_adaptation' => ['professional', 'agency'],
        'ai-agents.plan_access.content_dna' => ['professional', 'agency'],
        'ai-agents.plan_access.social_listening' => ['professional', 'agency'],
    ]);

    request()->attributes->set('auth_organization_id', $this->orgIdStr);
});

it('completes content creation pipeline end-to-end', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'cc-job-1'], 202),
        'http://ai-agents:8000/api/v1/jobs/cc-job-1' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'AI Generated', 'description' => 'Great content', 'hashtags' => ['#ai']],
            'metadata' => ['total_tokens' => 1200, 'total_cost' => 0.15, 'duration_ms' => 8000],
        ]),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'AI Marketing Trends',
        socialNetworks: ['instagram_feed'],
        tone: 'professional',
        keywords: ['ai', 'marketing'],
        language: 'pt-BR',
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('title')
        ->and($result->tokensInput)->toBe(1200)
        ->and($result->costEstimate)->toBe(0.15)
        ->and($result->durationMs)->toBe(8000);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/pipelines/content-creation'));
});

it('completes content DNA pipeline end-to-end', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-dna' => Http::response(['job_id' => 'dna-job-1'], 202),
        'http://ai-agents:8000/api/v1/jobs/dna-job-1' => Http::response([
            'status' => 'completed',
            'result' => ['style_profile' => ['tone' => 'professional', 'themes' => ['tech', 'ai']]],
            'metadata' => ['total_tokens' => 2000, 'total_cost' => 0.25, 'duration_ms' => 12000],
        ]),
    ]);

    $profiler = app(ContentProfileAnalyzerInterface::class);
    $result = $profiler->analyzeProfile(
        organizationId: $this->orgIdStr,
        publishedContents: [['id' => 1, 'text' => 'AI is transforming marketing']],
        metrics: [['likes' => 150, 'shares' => 30]],
    );

    expect($result)->toBeInstanceOf(ContentProfileResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('style_profile')
        ->and($result->tokensInput)->toBe(2000);
});

it('completes social listening pipeline end-to-end', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/social-listening' => Http::response(['job_id' => 'sl-job-1'], 202),
        'http://ai-agents:8000/api/v1/jobs/sl-job-1' => Http::response([
            'status' => 'completed',
            'result' => ['sentiment' => 'positive', 'priority' => 'low', 'response' => 'Thank you!'],
            'metadata' => ['total_tokens' => 800, 'total_cost' => 0.10, 'duration_ms' => 5000],
        ]),
    ]);

    $analyzer = app(MentionAnalyzerInterface::class);
    $result = $analyzer->analyzeMention(
        organizationId: $this->orgIdStr,
        mention: ['text' => 'Love this product!', 'author' => '@happy_user'],
        brandContext: ['name' => 'TestBrand', 'industry' => 'tech'],
    );

    expect($result)->toBeInstanceOf(MentionAnalysisResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('sentiment')
        ->and($result->costEstimate)->toBe(0.10);
});

it('completes visual adaptation pipeline end-to-end', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/visual-adaptation' => Http::response(['job_id' => 'va-job-1'], 202),
        'http://ai-agents:8000/api/v1/jobs/va-job-1' => Http::response([
            'status' => 'completed',
            'result' => ['adapted_images' => ['instagram_feed' => 'base64data', 'tiktok' => 'base64data2']],
            'metadata' => ['total_tokens' => 1500, 'total_cost' => 0.20, 'duration_ms' => 10000],
        ]),
    ]);

    $adapter = app(VisualAdapterInterface::class);
    $result = $adapter->adaptImage(
        organizationId: $this->orgIdStr,
        imageUrl: 'https://cdn.example.com/photo.jpg',
        targetNetworks: ['instagram_feed', 'tiktok'],
        brandGuidelines: ['colors' => ['#FF0000']],
    );

    expect($result)->toBeInstanceOf(VisualAdaptationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('adapted_images')
        ->and($result->durationMs)->toBe(10000);
});
