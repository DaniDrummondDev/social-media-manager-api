<?php

declare(strict_types=1);

use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;
use App\Infrastructure\Shared\Exceptions\AiAgentsRequestException;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;
use App\Infrastructure\Shared\Services\LangGraphClient;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->circuitBreaker = Mockery::mock(AiAgentsCircuitBreakerInterface::class);
    $this->client = new LangGraphClient($this->circuitBreaker);
    config(['ai-agents.base_url' => 'http://ai-agents:8000']);
    config(['ai-agents.internal_secret' => 'test-secret']);
    config(['ai-agents.callback_base_url' => 'http://nginx:80/api/v1/internal']);
});

it('dispatches pipeline and returns result on happy path', function () {
    $this->circuitBreaker->shouldReceive('isOpen')
        ->with('content_creation')
        ->once()
        ->andReturnFalse();

    $this->circuitBreaker->shouldReceive('recordSuccess')
        ->with('content_creation')
        ->once();

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response([
            'job_id' => 'test-job-123',
        ], 202),
        'http://ai-agents:8000/api/v1/jobs/test-job-123' => Http::response([
            'job_id' => 'test-job-123',
            'status' => 'completed',
            'result' => ['title' => 'Generated Title'],
            'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
        ]),
    ]);

    config(['ai-agents.poll_interval_ms' => 50]);

    $result = $this->client->dispatch('content_creation', [
        'organization_id' => 'org-123',
        'topic' => 'AI Marketing',
    ]);

    expect($result)
        ->toBeArray()
        ->toHaveKey('result')
        ->toHaveKey('metadata')
        ->and($result['result'])->toBe(['title' => 'Generated Title'])
        ->and($result['metadata'])->toBe(['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000]);
});

it('throws timeout exception when poll never completes', function () {
    $this->circuitBreaker->shouldReceive('isOpen')
        ->with('content_creation')
        ->once()
        ->andReturnFalse();

    $this->circuitBreaker->shouldReceive('recordFailure')
        ->with('content_creation')
        ->once();

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response([
            'job_id' => 'test-job-timeout',
        ], 202),
        'http://ai-agents:8000/api/v1/jobs/test-job-timeout' => Http::response([
            'job_id' => 'test-job-timeout',
            'status' => 'running',
        ]),
    ]);

    config(['ai-agents.poll_timeout' => 1]);
    config(['ai-agents.poll_interval_ms' => 100]);

    $this->client->dispatch('content_creation', [
        'organization_id' => 'org-123',
        'topic' => 'AI Marketing',
    ]);
})->throws(AiAgentsTimeoutException::class);

it('throws circuit open exception when circuit breaker is open', function () {
    $this->circuitBreaker->shouldReceive('isOpen')
        ->with('content_creation')
        ->once()
        ->andReturnTrue();

    Http::fake();

    $this->client->dispatch('content_creation', [
        'organization_id' => 'org-123',
        'topic' => 'AI Marketing',
    ]);

    Http::assertNothingSent();
})->throws(AiAgentsCircuitOpenException::class);

it('throws request exception and records failure on HTTP error', function () {
    $this->circuitBreaker->shouldReceive('isOpen')
        ->with('content_creation')
        ->once()
        ->andReturnFalse();

    $this->circuitBreaker->shouldReceive('recordFailure')
        ->with('content_creation')
        ->once();

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response([
            'error' => 'Internal Server Error',
        ], 500),
    ]);

    $this->client->dispatch('content_creation', [
        'organization_id' => 'org-123',
        'topic' => 'AI Marketing',
    ]);
})->throws(AiAgentsRequestException::class);

it('throws request exception when job status is failed', function () {
    $this->circuitBreaker->shouldReceive('isOpen')
        ->with('content_creation')->once()->andReturnFalse();
    $this->circuitBreaker->shouldReceive('recordFailure')
        ->with('content_creation')->once();

    Http::fake([
        'http://ai-agents:8000/api/v1/pipelines/content-creation' => Http::response([
            'job_id' => 'test-job-failed',
        ], 202),
        'http://ai-agents:8000/api/v1/jobs/test-job-failed' => Http::response([
            'job_id' => 'test-job-failed',
            'status' => 'failed',
            'error' => 'LLM quota exceeded',
        ]),
    ]);

    config(['ai-agents.poll_interval_ms' => 50]);

    $this->client->dispatch('content_creation', [
        'organization_id' => 'org-123',
        'topic' => 'AI Marketing',
    ]);
})->throws(AiAgentsRequestException::class);
