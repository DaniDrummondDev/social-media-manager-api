<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GetAdTargetingSuggestionsInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $contentId,
    ) {}
}
