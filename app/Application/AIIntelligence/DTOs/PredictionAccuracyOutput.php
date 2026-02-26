<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class PredictionAccuracyOutput
{
    public function __construct(
        public float $meanAbsoluteError,
        public int $totalValidations,
        public string $message,
    ) {}
}
