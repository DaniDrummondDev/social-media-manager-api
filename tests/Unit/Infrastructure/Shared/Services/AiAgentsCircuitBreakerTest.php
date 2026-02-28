<?php

declare(strict_types=1);

use App\Infrastructure\Shared\Services\AiAgentsCircuitBreaker;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->circuitBreaker = new AiAgentsCircuitBreaker();
    config(['ai-agents.circuit_breaker.failure_threshold' => 3]);
    config(['ai-agents.circuit_breaker.open_timeout' => 120]);
});

it('is not open when no failures recorded', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->andReturnNull();

    expect($this->circuitBreaker->isOpen('content_creation'))->toBeFalse();
});

it('opens circuit after reaching failure threshold', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);

    // First failure
    $cache->shouldReceive('increment')
        ->with('circuit:ai_agents:content_creation:failures')
        ->once()
        ->andReturn(1);
    $cache->shouldReceive('put')
        ->with('circuit:ai_agents:content_creation:failures', 1, 120)
        ->once();

    $this->circuitBreaker->recordFailure('content_creation');

    // Second failure
    $cache->shouldReceive('increment')
        ->with('circuit:ai_agents:content_creation:failures')
        ->once()
        ->andReturn(2);

    $this->circuitBreaker->recordFailure('content_creation');

    // Third failure — threshold reached, circuit opens
    $cache->shouldReceive('increment')
        ->with('circuit:ai_agents:content_creation:failures')
        ->once()
        ->andReturn(3);
    $cache->shouldReceive('put')
        ->with('circuit:ai_agents:content_creation:open_until', Mockery::type('int'), 120)
        ->once();

    $this->circuitBreaker->recordFailure('content_creation');

    // Verify circuit is now open
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->andReturn(time() + 120);

    expect($this->circuitBreaker->isOpen('content_creation'))->toBeTrue();
});

it('resets circuit on success', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);

    $cache->shouldReceive('forget')
        ->with('circuit:ai_agents:content_creation:failures')
        ->once();
    $cache->shouldReceive('forget')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->once();

    $this->circuitBreaker->recordSuccess('content_creation');
});
