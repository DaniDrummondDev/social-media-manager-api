<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Domain\AIIntelligence\Contracts\AdIntelligenceProviderInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class StubAdIntelligenceProvider implements AdIntelligenceProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getBestAudiences(Uuid $organizationId): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getBestContentForAds(Uuid $organizationId): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganicVsPaidCorrelation(Uuid $organizationId): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTargetingSuggestions(Uuid $organizationId, Uuid $contentId): array
    {
        return [];
    }

    public function getAdPerformanceBoost(Uuid $organizationId, Uuid $contentId): float
    {
        return 0.0;
    }
}
