<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GetContentRecommendationsInput
{
    public function __construct(
        public string $organizationId,
        public string $topic,
        public int $limit = 5,
        public ?string $provider = null,
    ) {}
}
