<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class RunSafetyCheckOutput
{
    public function __construct(
        public string $checkId,
        public string $contentId,
        public string $status,
        public string $message,
    ) {}
}
