<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class GenerateHashtagsInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $topic,
        public ?string $niche = null,
        public ?string $socialNetwork = null,
    ) {}
}
