<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;

final class StubPredictionValidator implements PredictionValidatorInterface
{
    public function normalizeEngagementRate(
        string $organizationId,
        string $provider,
        float $engagementRate,
    ): int {
        // Stub: simple linear normalization (0-10% engagement → 0-100 score)
        return min(100, max(0, (int) round($engagementRate * 10)));
    }
}
