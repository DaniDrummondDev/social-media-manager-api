<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class RunSafetyCheckInput
{
    /**
     * @param  array<string>|null  $providers
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $contentId,
        public ?array $providers = null,
    ) {}
}
