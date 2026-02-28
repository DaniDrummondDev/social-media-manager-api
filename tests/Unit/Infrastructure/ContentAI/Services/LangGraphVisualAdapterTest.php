<?php

declare(strict_types=1);

use App\Application\ContentAI\DTOs\VisualAdaptationResult;
use App\Infrastructure\ContentAI\Services\LangGraphVisualAdapter;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

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
