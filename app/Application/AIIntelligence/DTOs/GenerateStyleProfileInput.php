<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateStyleProfileInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $generationType,
    ) {}
}
