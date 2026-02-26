<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class CalculatePromptPerformanceInput
{
    public function __construct(
        public string $userId,
        public ?string $organizationId = null,
        public ?string $generationType = null,
    ) {}
}
