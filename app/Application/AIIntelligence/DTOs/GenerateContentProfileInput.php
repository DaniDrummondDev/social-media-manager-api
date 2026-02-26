<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateContentProfileInput
{
    public function __construct(
        public string $organizationId,
        public ?string $provider = null,
        public ?string $socialAccountId = null,
        public string $userId = 'system',
    ) {}
}
