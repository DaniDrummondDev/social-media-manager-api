<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\VisualAdaptationResult;
use App\Infrastructure\ContentAI\Services\LangGraphVisualAdapter;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;

beforeEach(function () {
    $this->client = Mockery::mock(LangGraphClientInterface::class);
    $this->adapter = new LangGraphVisualAdapter($this->client);
});

it('adapts image via LangGraph and returns result', function () {
    $this->client->shouldReceive('dispatch')
        ->with('visual_adaptation', [
            'organization_id' => 'org-456',
            'image_url' => 'https://cdn.example.com/image.jpg',
            'target_networks' => ['instagram_feed', 'tiktok'],
            'brand_guidelines' => ['primary_color' => '#FF5733'],
        ])
        ->once()
        ->andReturn([
            'result' => [
                'adapted_images' => [
                    ['network' => 'instagram_feed', 'url' => 'https://cdn.example.com/ig.jpg'],
                    ['network' => 'tiktok', 'url' => 'https://cdn.example.com/tt.jpg'],
                ],
            ],
            'metadata' => ['total_tokens' => 300, 'total_cost' => 0.03, 'duration_ms' => 5000],
        ]);

    $result = $this->adapter->adaptImage(
        organizationId: 'org-456',
        imageUrl: 'https://cdn.example.com/image.jpg',
        targetNetworks: ['instagram_feed', 'tiktok'],
        brandGuidelines: ['primary_color' => '#FF5733'],
    );

    expect($result)
        ->toBeInstanceOf(VisualAdaptationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent')
        ->and($result->output)->toHaveKey('adapted_images')
        ->and($result->tokensInput)->toBe(300)
        ->and($result->costEstimate)->toBe(0.03)
        ->and($result->durationMs)->toBe(5000);
});

it('throws exception when circuit breaker is open', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new AiAgentsCircuitOpenException('visual_adaptation'));

    $this->adapter->adaptImage(
        organizationId: 'org-123', imageUrl: 'https://example.com/image.jpg',
        targetNetworks: ['instagram_feed'], brandGuidelines: null,
    );
})->throws(AiAgentsCircuitOpenException::class);

it('dispatches with null brand guidelines', function () {
    $this->client->shouldReceive('dispatch')
        ->with('visual_adaptation', Mockery::on(fn ($p) => $p['brand_guidelines'] === null))
        ->once()
        ->andReturn([
            'result' => ['adapted' => true],
            'metadata' => ['total_tokens' => 600, 'total_cost' => 0.08, 'duration_ms' => 4000],
        ]);

    $result = $this->adapter->adaptImage(
        organizationId: 'org-123', imageUrl: 'https://example.com/image.jpg',
        targetNetworks: ['tiktok', 'youtube'], brandGuidelines: null,
    );

    expect($result)->toBeInstanceOf(VisualAdaptationResult::class)
        ->and($result->model)->toBe('langgraph_multi_agent');
});

it('throws timeout exception when poll never completes', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException('visual_adaptation', 'job-timeout-123'));

    $this->adapter->adaptImage(
        organizationId: 'org-timeout',
        imageUrl: 'https://example.com/timeout.jpg',
        targetNetworks: ['instagram_feed'],
        brandGuidelines: null,
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException::class);

it('throws request exception on HTTP error', function () {
    $this->client->shouldReceive('dispatch')
        ->once()
        ->andThrow(new \App\Infrastructure\Shared\Exceptions\AiAgentsRequestException('visual_adaptation', 'HTTP 500 Internal Server Error'));

    $this->adapter->adaptImage(
        organizationId: 'org-error',
        imageUrl: 'https://example.com/error.jpg',
        targetNetworks: ['tiktok'],
        brandGuidelines: ['color' => '#000'],
    );
})->throws(\App\Infrastructure\Shared\Exceptions\AiAgentsRequestException::class);

it('validates organization_id is included in payload', function () {
    $this->client->shouldReceive('dispatch')
        ->with('visual_adaptation', Mockery::on(function ($payload) {
            return $payload['organization_id'] === 'org-validate-123'
                && isset($payload['image_url'])
                && isset($payload['target_networks'])
                && isset($payload['brand_guidelines']);
        }))
        ->once()
        ->andReturn([
            'result' => ['adapted_images' => []],
            'metadata' => ['total_tokens' => 100, 'total_cost' => 0.01, 'duration_ms' => 2000],
        ]);

    $result = $this->adapter->adaptImage(
        organizationId: 'org-validate-123',
        imageUrl: 'https://example.com/validate.jpg',
        targetNetworks: ['instagram_feed'],
        brandGuidelines: ['primary_color' => '#FF0000'],
    );

    expect($result)->toBeInstanceOf(VisualAdaptationResult::class);
});
