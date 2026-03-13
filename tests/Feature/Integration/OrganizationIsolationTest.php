<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\ContentProfileAnalyzerInterface;
use App\Application\AIIntelligence\Contracts\MentionAnalyzerInterface;
use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'ai-agents.base_url' => 'http://ai-agents:8000',
        'ai-agents.internal_secret' => 'test-secret',
        'ai-agents.poll_interval_ms' => 10,
        'ai-agents.poll_timeout' => 5,
    ]);

    $planGate = Mockery::mock(AiAgentsPlanGate::class);
    $planGate->shouldReceive('canAccess')->andReturnTrue();
    $this->app->instance(AiAgentsPlanGate::class, $planGate);

    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordSuccess')->andReturnNull();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);
});

it('passes organization_id to content-creation pipeline payload', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'org-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/org-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Org Specific'],
            'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
        ]),
    ]);

    request()->attributes->set('auth_organization_id', 'org-abc-123');

    $generator = app(TextGeneratorInterface::class);
    $generator->generateFullContent(
        topic: 'Org Test',
        socialNetworks: ['instagram_feed'],
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/pipelines/content-creation')
            && $body['organization_id'] === 'org-abc-123';
    });
});

it('passes organization_id to content-dna pipeline payload', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-dna' => Http::response(['job_id' => 'dna-org-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/dna-org-job' => Http::response([
            'status' => 'completed',
            'result' => ['style_profile' => ['tone' => 'casual']],
            'metadata' => ['total_tokens' => 800, 'total_cost' => 0.08, 'duration_ms' => 4000],
        ]),
    ]);

    $profiler = app(ContentProfileAnalyzerInterface::class);
    $profiler->analyzeProfile(
        organizationId: 'org-dna-xyz',
        publishedContents: [['text' => 'Sample post']],
        metrics: [['likes' => 50]],
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/pipelines/content-dna')
            && $body['organization_id'] === 'org-dna-xyz';
    });
});

it('passes organization_id to social-listening pipeline payload', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/social-listening' => Http::response(['job_id' => 'sl-org-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/sl-org-job' => Http::response([
            'status' => 'completed',
            'result' => ['sentiment' => 'neutral', 'priority' => 'medium'],
            'metadata' => ['total_tokens' => 300, 'total_cost' => 0.03, 'duration_ms' => 2000],
        ]),
    ]);

    $analyzer = app(MentionAnalyzerInterface::class);
    $analyzer->analyzeMention(
        organizationId: 'org-mention-456',
        mention: ['text' => 'Good product', 'author' => '@user'],
        brandContext: ['name' => 'BrandX'],
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return str_contains($request->url(), '/pipelines/social-listening')
            && $body['organization_id'] === 'org-mention-456';
    });
});

it('sends X-Internal-Secret header in every pipeline request', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'secret-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/secret-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Secure'],
            'metadata' => ['total_tokens' => 100, 'total_cost' => 0.01, 'duration_ms' => 1000],
        ]),
    ]);

    request()->attributes->set('auth_organization_id', 'org-secret-test');

    $generator = app(TextGeneratorInterface::class);
    $generator->generateFullContent(
        topic: 'Secret Header Test',
        socialNetworks: ['instagram_feed'],
    );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/pipelines/content-creation')
            && $request->header('X-Internal-Secret')[0] === 'test-secret';
    });
});
