<?php

declare(strict_types=1);

namespace App\Application\ContentAI\Contracts;

use App\Application\ContentAI\DTOs\TextGenerationResult;

interface TextGeneratorInterface
{
    public function generateTitle(string $topic, ?string $socialNetwork = null, ?string $tone = null, ?string $language = null): TextGenerationResult;

    /**
     * @param  string[]  $keywords
     */
    public function generateDescription(string $topic, ?string $socialNetwork = null, ?string $tone = null, array $keywords = [], ?string $language = null): TextGenerationResult;

    public function generateHashtags(string $topic, ?string $niche = null, ?string $socialNetwork = null): TextGenerationResult;

    /**
     * @param  string[]  $socialNetworks
     * @param  string[]  $keywords
     */
    public function generateFullContent(string $topic, array $socialNetworks, ?string $tone = null, array $keywords = [], ?string $language = null): TextGenerationResult;
}
