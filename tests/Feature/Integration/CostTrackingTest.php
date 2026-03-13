<?php

declare(strict_types=1);

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
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

    // Mock plan gate to always allow access
    $planGate = Mockery::mock(AiAgentsPlanGate::class);
    $planGate->shouldReceive('canAccess')->andReturnTrue();
    $this->app->instance(AiAgentsPlanGate::class, $planGate);

    // Mock circuit breaker to always allow
    $circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $circuitBreaker->shouldReceive('isOpen')->andReturnFalse();
    $circuitBreaker->shouldReceive('recordSuccess')->andReturnNull();
    $this->app->instance(AiAgentsCircuitBreakerInterface::class, $circuitBreaker);

    request()->attributes->set('auth_organization_id', 'org-cost-test');
});

it('receives metadata with tokens and cost from pipeline result', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'cost-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/cost-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Cost Tracked Content'],
            'metadata' => [
                'total_tokens' => 2500,
                'total_cost' => 0.35,
                'duration_ms' => 15000,
                'agents_used' => ['planner', 'writer', 'reviewer', 'optimizer'],
            ],
        ]),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Cost Test Topic',
        socialNetworks: ['instagram_feed'],
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->tokensInput)->toBe(2500)
        ->and($result->costEstimate)->toBe(0.35)
        ->and($result->durationMs)->toBe(15000)
        ->and($result->model)->toBe('langgraph_multi_agent');
});

it('maps zero values correctly when metadata fields are missing', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'empty-meta-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/empty-meta-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Minimal Result'],
            'metadata' => [],
        ]),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Minimal Test',
        socialNetworks: ['tiktok'],
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->tokensInput)->toBe(0)
        ->and($result->costEstimate)->toBe(0.0)
        ->and($result->durationMs)->toBe(0)
        ->and($result->tokensOutput)->toBe(0)
        ->and($result->model)->toBe('langgraph_multi_agent');
});

it('includes agents_used in metadata', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'agents-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/agents-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Multi-Agent Content'],
            'metadata' => [
                'total_tokens' => 3000,
                'total_cost' => 0.45,
                'duration_ms' => 18000,
                'agents_used' => ['planner', 'writer', 'reviewer', 'optimizer'],
            ],
        ]),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Multi-Agent Test',
        socialNetworks: ['instagram_feed'],
    );

    expect($result)->toBeInstanceOf(TextGenerationResult::class)
        ->and($result->tokensInput)->toBe(3000)
        ->and($result->costEstimate)->toBe(0.45)
        ->and($result->durationMs)->toBe(18000);
});

it('tracks duration_ms correctly', function () {
    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response(['job_id' => 'duration-job'], 202),
        'http://ai-agents:8000/api/v1/jobs/duration-job' => Http::response([
            'status' => 'completed',
            'result' => ['title' => 'Duration Test'],
            'metadata' => [
                'total_tokens' => 1000,
                'total_cost' => 0.12,
                'duration_ms' => 25000,
            ],
        ]),
    ]);

    $generator = app(TextGeneratorInterface::class);
    $result = $generator->generateFullContent(
        topic: 'Duration Test',
        socialNetworks: ['tiktok'],
    );

    expect($result->durationMs)->toBe(25000)
        ->and($result->model)->toBe('langgraph_multi_agent');
});
