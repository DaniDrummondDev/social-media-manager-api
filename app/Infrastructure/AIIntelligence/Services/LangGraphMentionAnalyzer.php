<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\MentionAnalyzerInterface;
use App\Application\AIIntelligence\DTOs\MentionAnalysisResult;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;

final class LangGraphMentionAnalyzer implements MentionAnalyzerInterface
{
    public function __construct(
        private readonly LangGraphClientInterface $client,
    ) {}

    /**
     * @param  array<string, mixed>  $mention
     * @param  array<string, mixed>  $brandContext
     */
    public function analyzeMention(
        string $organizationId,
        array $mention,
        array $brandContext,
        string $language = 'pt-BR',
    ): MentionAnalysisResult {
        $response = $this->client->dispatch('social_listening', [
            'organization_id' => $organizationId,
            'mention' => $mention,
            'brand_context' => $brandContext,
            'language' => $language,
        ]);

        $metadata = $response['metadata'];

        return new MentionAnalysisResult(
            output: $response['result'],
            tokensInput: (int) ($metadata['total_tokens'] ?? 0),
            tokensOutput: 0,
            model: 'langgraph_multi_agent',
            durationMs: (int) ($metadata['duration_ms'] ?? 0),
            costEstimate: (float) ($metadata['total_cost'] ?? 0.0),
        );
    }
}
