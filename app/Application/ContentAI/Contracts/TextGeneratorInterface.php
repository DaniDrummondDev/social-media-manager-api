<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Contracts;

use App\Application\ContentAI\DTOs\TextGenerationResult;

interface TextGeneratorInterface
{
    /**
     * Generate title suggestions for a given topic.
     * When organizationId is provided, enrichment from RAG, style profiles, and templates is applied.
     */
    public function generateTitle(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult;

    /**
     * Generate description/caption for a given topic.
     *
     * @param  string[]  $keywords
     */
    public function generateDescription(
        string $topic,
        ?string $socialNetwork = null,
        ?string $tone = null,
        array $keywords = [],
        ?string $language = null,
        ?string $organizationId = null,
    ): TextGenerationResult;

    /**
     * Generate relevant hashtags for a given topic.
     */
    public function generateHashtags(
        string $topic,
        ?string $niche = null,
        ?string $socialNetwork = null,
        ?string $organizationId = null,
    ): TextGenerationResult;

    /**
     * Generate full content for multiple social networks.
     *
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
    ): TextGenerationResult;

    /**
     * Adapt content from one network to target networks.
     *
     * @param  string[]  $targetNetworks
     */
    public function adaptContent(
        string $contentId,
        string $organizationId,
        string $sourceNetwork,
        array $targetNetworks,
        bool $preserveTone,
    ): TextGenerationResult;
}
