<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class GenerateFullContentInput
{
    /**
     * @param  string[]  $socialNetworks
     * @param  string[]  $keywords
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $topic,
        public array $socialNetworks,
        public ?string $tone = null,
        public array $keywords = [],
        public ?string $language = null,
    ) {}
}
