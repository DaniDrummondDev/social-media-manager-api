<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\VisualAdapterInterface;
use App\Application\ContentAI\DTOs\VisualAdaptationResult;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

final class LangGraphVisualAdapter implements VisualAdapterInterface
{
    public function __construct(
        private readonly LangGraphClientInterface $client,
    ) {}

    /**
     * @param  list<string>  $targetNetworks
     * @param  array<string, mixed>|null  $brandGuidelines
     */
    public function adaptImage(
        string $organizationId,
        string $imageUrl,
        array $targetNetworks,
        ?array $brandGuidelines = null,
    ): VisualAdaptationResult {
        $response = $this->client->dispatch('visual_adaptation', [
            'organization_id' => $organizationId,
            'image_url' => $imageUrl,
            'target_networks' => $targetNetworks,
            'brand_guidelines' => $brandGuidelines,
        ]);

        $metadata = $response['metadata'];

        return new VisualAdaptationResult(
            output: $response['result'],
            tokensInput: (int) ($metadata['total_tokens'] ?? 0),
            tokensOutput: 0,
            model: 'langgraph_multi_agent',
            durationMs: (int) ($metadata['duration_ms'] ?? 0),
            costEstimate: (float) ($metadata['total_cost'] ?? 0.0),
        );
    }
}
