<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\AudienceInsight;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\ValueObjects\Uuid;

interface AudienceInsightRepositoryInterface
{
    public function create(AudienceInsight $insight): void;

    public function findById(Uuid $id): ?AudienceInsight;

    public function findByOrganizationAndType(
        Uuid $organizationId,
        InsightType $type,
        ?Uuid $socialAccountId = null,
    ): ?AudienceInsight;

    /**
     * @return array<AudienceInsight>
     */
    public function findActiveByOrganization(Uuid $organizationId): array;
}
