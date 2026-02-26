<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\DTOs;

final readonly class ValidatePredictionInput
{
    /**
     * @param  array<string, mixed>  $metricsSnapshot
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $predictionId,
        public string $contentId,
        public string $provider,
        public float $actualEngagementRate,
        public array $metricsSnapshot,
        public string $metricsCapturedAt,
    ) {}
}
