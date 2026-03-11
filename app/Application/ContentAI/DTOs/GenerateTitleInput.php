<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class GenerateTitleInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $topic = '',
        public ?string $socialNetwork = null,
        public ?string $tone = null,
        public ?string $language = null,
        public ?string $campaignId = null,
        public string $generationMode = 'fields_only',
    ) {}
}
