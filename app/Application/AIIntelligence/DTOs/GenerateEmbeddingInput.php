<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateEmbeddingInput
{
    public function __construct(
        public string $organizationId,
        public string $entityType,
        public string $entityId,
        public string $text,
        public string $userId,
    ) {}
}
