<?php

declare(strict_types=1);

namespace App\Application\ContentAI\DTOs;

final readonly class CreatePromptExperimentInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $generationType,
        public string $name,
        public string $variantAId,
        public string $variantBId,
        public float $trafficSplit = 0.5,
        public int $minSampleSize = 50,
    ) {}
}
