<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Contracts;

use App\Domain\Shared\ValueObjects\Uuid;

interface CrmIntelligenceProviderInterface
{
    public function getConversionBoost(Uuid $contentId, Uuid $organizationId): float;

    /**
     * @return array<string, mixed>
     */
    public function getConversionSummary(Uuid $organizationId): array;

    /**
     * @return array<string, mixed>
     */
    public function getAudienceSegments(Uuid $organizationId): array;
}
