<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class RecalculateBestTimesInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public ?string $provider = null,
        public ?string $socialAccountId = null,
    ) {}
}
