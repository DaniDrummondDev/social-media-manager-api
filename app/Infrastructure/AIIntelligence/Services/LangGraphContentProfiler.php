<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\ContentProfileAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\ContentProfileResult;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

final class LangGraphContentProfiler implements ContentProfileAnalyzerInterface
{
    public function __construct(
        private readonly LangGraphClientInterface $client,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $publishedContents
     * @param  array<int, array<string, mixed>>  $metrics
     * @param  array<string, mixed>|null  $currentStyleProfile
     */
    public function analyzeProfile(
        string $organizationId,
        array $publishedContents,
        array $metrics,
        ?array $currentStyleProfile = null,
        string $timeWindow = 'last_90_days',
    ): ContentProfileResult {
        $response = $this->client->dispatch('content_dna', [
            'organization_id' => $organizationId,
            'published_contents' => $publishedContents,
            'metrics' => $metrics,
            'current_style_profile' => $currentStyleProfile,
            'time_window' => $timeWindow,
        ]);

        $metadata = $response['metadata'];

        return new ContentProfileResult(
            output: $response['result'],
            tokensInput: (int) ($metadata['total_tokens'] ?? 0),
            tokensOutput: 0,
            model: 'langgraph_multi_agent',
            durationMs: (int) ($metadata['duration_ms'] ?? 0),
            costEstimate: (float) ($metadata['total_cost'] ?? 0.0),
        );
    }
}
