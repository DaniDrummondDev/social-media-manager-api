<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Contracts;

use App\Domain\Shared\ValueObjects\Uuid;

interface AdIntelligenceProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getBestAudiences(Uuid $organizationId): array;

    /**
     * @return array<string, mixed>
     */
    public function getBestContentForAds(Uuid $organizationId): array;

    /**
     * @return array<string, mixed>
     */
    public function getOrganicVsPaidCorrelation(Uuid $organizationId): array;

    /**
     * @return array<string, mixed>
     */
    public function getTargetingSuggestions(Uuid $organizationId, Uuid $contentId): array;

    public function getAdPerformanceBoost(Uuid $organizationId, Uuid $contentId): float;
}
