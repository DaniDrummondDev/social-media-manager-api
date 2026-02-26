<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Contracts;

interface PredictionValidatorInterface
{
    /**
     * Normalize a raw engagement rate to a 0-100 percentile rank
     * based on the organization's own historical distribution.
     *
     * Normalization is always per-org, never cross-org (RN-ALL-33).
     */
    public function normalizeEngagementRate(
        string $organizationId,
        string $provider,
        float $engagementRate,
    ): int;
}
