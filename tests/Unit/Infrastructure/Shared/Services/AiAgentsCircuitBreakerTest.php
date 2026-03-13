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

it('closes circuit when open_until expires (half-open transition)', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->andReturn(time() - 10); // expired 10 seconds ago

    expect($this->circuitBreaker->isOpen('content_creation'))->toBeFalse();
});

it('resets after success following half-open state', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);

    // Circuit was open but expired (half-open)
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->andReturn(time() - 10);

    expect($this->circuitBreaker->isOpen('content_creation'))->toBeFalse();

    // Success resets everything
    $cache->shouldReceive('forget')
        ->with('circuit:ai_agents:content_creation:failures')->once();
    $cache->shouldReceive('forget')
        ->with('circuit:ai_agents:content_creation:open_until')->once();

    $this->circuitBreaker->recordSuccess('content_creation');
});

it('isolates circuit state between different pipelines', function () {
    $cache = Mockery::mock();
    Cache::shouldReceive('store')->with('redis')->andReturn($cache);

    // content_creation is open
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:content_creation:open_until')
        ->andReturn(time() + 120);

    // visual_adaptation is not open
    $cache->shouldReceive('get')
        ->with('circuit:ai_agents:visual_adaptation:open_until')
        ->andReturnNull();

    expect($this->circuitBreaker->isOpen('content_creation'))->toBeTrue()
        ->and($this->circuitBreaker->isOpen('visual_adaptation'))->toBeFalse();
});
