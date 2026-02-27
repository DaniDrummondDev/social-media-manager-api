<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\Shared\ValueObjects\Uuid;

interface AdPerformanceInsightRepositoryInterface
{
    public function save(AdPerformanceInsight $insight): void;

    public function findById(Uuid $id): ?AdPerformanceInsight;

    public function findByOrganizationAndType(
        Uuid $organizationId,
        AdInsightType $type,
    ): ?AdPerformanceInsight;

    /**
     * @return array<AdPerformanceInsight>
     */
    public function findActiveByOrganization(Uuid $organizationId): array;

    /**
     * @return array<AdPerformanceInsight>
     */
    public function findExpired(): array;
}
