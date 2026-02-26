<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class GetPredictionAccuracyInput
{
    public function __construct(
        public string $organizationId,
    ) {}
}
