<?php

declare(strict_types=1);

namespace App\Infrastructure\ContentAI\Services;

use App\Application\ContentAI\Contracts\TextGeneratorInterface;
use App\Application\ContentAI\DTOs\TextGenerationResult;
use App\Infrastructure\Shared\Contracts\LangGraphClientInterface;
use App\Infrastructure\Shared\Exceptions\AiAgentsCircuitOpenException;
use App\Infrastructure\Shared\Exceptions\AiAgentsRequestException;
use App\Infrastructure\Shared\Exceptions\AiAgentsTimeoutException;
use App\Infrastructure\Shared\Services\AiAgentsPlanGate;

final class LangGraphTextGenerator implements TextGeneratorInterface
{
    public function __construct(
        private readonly LangGraphClientInterface $client,
        private readonly TextGeneratorInterface $fallback,
        private readonly AiAgentsPlanGate $planGate,
    ) {}

    public function generateTitle(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        return $this->dispatchOrFallback(
            fn () => $this->fallback->generateTitle($topic, $socialNetwork, $tone, $language, $organizationId),
            topic: $topic,
            provider: $socialNetwork ?? 'instagram_feed',
            tone: $tone ?? 'professional',
            language: $language ?? 'pt-BR',
            organizationId: $organizationId,
        );
    }

    /**
     * @param  string[]  $keywords
     */
    public function generateDescription(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        array $keywords = [],
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        return $this->dispatchOrFallback(
            fn () => $this->fallback->generateDescription($topic, $socialNetwork, $tone, $keywords, $language, $organizationId),
            topic: $topic,
            provider: $socialNetwork ?? 'instagram_feed',
            tone: $tone ?? 'professional',
            keywords: $keywords,
            language: $language ?? 'pt-BR',
            organizationId: $organizationId,
        );
    }

    public function generateHashtags(
        string $topic,
        ?string $niche = null,
        ?string $socialNetwork = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        return $this->dispatchOrFallback(
            fn () => $this->fallback->generateHashtags($topic, $niche, $socialNetwork, $organizationId),
            topic: $topic,
            provider: $socialNetwork ?? 'instagram_feed',
            organizationId: $organizationId,
        );
    }

    /**
     * @param  string[]  $socialNetworks
     * @param  string[]  $keywords
     */
    public function generateFullContent(
        string $topic,
        array $socialNetworks,
        ?string $tone = null,
        array $keywords = [],
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult {
        return $this->dispatchOrFallback(
            fn () => $this->fallback->generateFullContent($topic, $socialNetworks, $tone, $keywords, $language, $organizationId),
            topic: $topic,
            provider: $socialNetworks[0] ?? 'instagram_feed',
            tone: $tone ?? 'professional',
            keywords: $keywords,
            language: $language ?? 'pt-BR',
            organizationId: $organizationId,
        );
    }

    /**
     * @param  string[]  $targetNetworks
     */
    public function adaptContent(
        string $contentId,
        string $organizationId,
        string $sourceNetwork,
        array $targetNetworks,
        bool $preserveTone,
    ): TextGenerationResult {
        return $this->dispatchOrFallback(
            fn () => $this->fallback->adaptContent($contentId, $organizationId, $sourceNetwork, $targetNetworks, $preserveTone),
            topic: "Adapt content {$contentId} from {$sourceNetwork}",
            provider: $targetNetworks[0] ?? 'instagram_feed',
            organizationId: $organizationId,
        );
    }

    /**
     * @param  callable(): TextGenerationResult  $fallbackFn
     * @param  string[]  $keywords
     */
    private function dispatchOrFallback(
        callable $fallbackFn,
        string $topic,
        string $provider = 'instagram_feed',
        string $tone = 'professional',
        array $keywords = [],
        string $language = 'pt-BR',
        ?string $organizationId = null,
    ): TextGenerationResult {
        $orgId = $organizationId ?? $this->resolveOrganizationId();

        if ($orgId !== null && ! $this->planGate->canAccess($orgId, 'content_creation')) {
            return $fallbackFn();
        }

        try {
            $response = $this->client->dispatch('content_creation', [
                'organization_id' => $orgId ?? '',
                'topic' => $topic,
                'provider' => $provider,
                'tone' => $tone,
                'keywords' => $keywords,
                'language' => $language,
            ]);

            return $this->mapToResult($response);
        } catch (AiAgentsCircuitOpenException|AiAgentsTimeoutException|AiAgentsRequestException) {
            return $fallbackFn();
        }
    }

    /**
     * @param  array{result: array<string, mixed>, metadata: array<string, mixed>}  $response
     */
    private function mapToResult(array $response): TextGenerationResult
    {
        $metadata = $response['metadata'];

        return new TextGenerationResult(
            output: $response['result'],
            tokensInput: (int) ($metadata['total_tokens'] ?? 0),
            tokensOutput: 0,
            model: 'langgraph_multi_agent',
            durationMs: (int) ($metadata['duration_ms'] ?? 0),
            costEstimate: (float) ($metadata['total_cost'] ?? 0.0),
        );
    }

    private function resolveOrganizationId(): ?string
    {
        /** @var string|null $orgId */
        $orgId = request()?->attributes?->get('auth_organization_id');

        return $orgId;
    }
}
