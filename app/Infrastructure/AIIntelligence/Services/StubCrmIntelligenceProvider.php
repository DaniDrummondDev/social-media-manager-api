<?php

declare(strict_types=1);

namespace App\Infrastructure\AIIntelligence\Services;

use App\Domain\AIIntelligence\Contracts\CrmIntelligenceProviderInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class StubCrmIntelligenceProvider implements CrmIntelligenceProviderInterface
{
    public function getConversionBoost(Uuid $contentId, Uuid $organizationId): float
    {
        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConversionSummary(Uuid $organizationId): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAudienceSegments(Uuid $organizationId): array
    {
        return [];
    }
}
