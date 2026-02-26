<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GenerateGapAnalysisOutput
{
    public function __construct(
        public string $analysisId,
        public string $status,
        public string $message,
    ) {}
}
