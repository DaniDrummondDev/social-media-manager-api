<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateContentProfileOutput
{
    public function __construct(
        public string $profileId,
        public string $status,
        public string $message,
    ) {}
}
