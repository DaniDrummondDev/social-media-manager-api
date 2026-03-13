<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['ai-agents.internal_secret' => 'test-secret-123']);
});

it('accepts valid callback and returns 202', function () {
    $redisStore = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $redisStore->shouldReceive('put')->once()->andReturnTrue();

    Cache::shouldReceive('store')->with('redis')->once()->andReturn($redisStore);

    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => '550e8400-e29b-41d4-a716-446655440000',
        'job_id' => 'job-abc-123',
        'status' => 'completed',
        'result' => ['title' => 'Generated'],
        'metadata' => ['total_tokens' => 500, 'total_cost' => 0.05, 'duration_ms' => 3000],
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(202)
        ->assertJson(['status' => 'accepted']);
});

it('rejects request with invalid secret returning 403', function () {
    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => '550e8400-e29b-41d4-a716-446655440000',
        'job_id' => 'job-abc-123',
        'status' => 'completed',
        'result' => ['title' => 'Generated'],
    ], [
        'X-Internal-Secret' => 'wrong-secret',
    ]);

    $response->assertStatus(403);
});

it('validates required fields and returns 422', function () {
    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'job_id' => 'job-abc-123',
        'status' => 'completed',
        // missing correlation_id
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(422);
});

it('validates correlation_id must be uuid', function () {
    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => 'not-a-uuid',
        'job_id' => 'job-abc-123',
        'status' => 'completed',
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(422);
});

it('validates status must be completed or failed', function () {
    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => '550e8400-e29b-41d4-a716-446655440000',
        'job_id' => 'job-abc-123',
        'status' => 'running',
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(422);
});

it('accepts callback with failed status', function () {
    $redisStore = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $redisStore->shouldReceive('put')
        ->with('agent_callback:job-fail-123', Mockery::on(function ($json) {
            $data = json_decode($json, true);
            return $data['status'] === 'failed';
        }), 600)
        ->once()
        ->andReturnTrue();

    Cache::shouldReceive('store')->with('redis')->once()->andReturn($redisStore);

    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => '550e8400-e29b-41d4-a716-446655440000',
        'job_id' => 'job-fail-123',
        'status' => 'failed',
        'result' => null,
        'metadata' => null,
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(202)->assertJson(['status' => 'accepted']);
});

it('accepts callback with null result and metadata', function () {
    $redisStore = Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $redisStore->shouldReceive('put')->once()->andReturnTrue();

    Cache::shouldReceive('store')->with('redis')->once()->andReturn($redisStore);

    $response = $this->postJson('/api/v1/internal/agent-callback', [
        'correlation_id' => '550e8400-e29b-41d4-a716-446655440000',
        'job_id' => 'job-null-123',
        'status' => 'completed',
    ], [
        'X-Internal-Secret' => 'test-secret-123',
    ]);

    $response->assertStatus(202);
});
