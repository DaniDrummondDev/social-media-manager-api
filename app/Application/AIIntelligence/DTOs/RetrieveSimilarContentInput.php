<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class RetrieveSimilarContentInput
{
    public function __construct(
        public string $organizationId,
        public string $topic,
        public ?string $provider = null,
        public int $limit = 5,
    ) {}
}
