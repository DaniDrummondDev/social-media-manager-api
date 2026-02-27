<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\CrmConversionAttribution;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Shared\ValueObjects\Uuid;

interface CrmConversionAttributionRepositoryInterface
{
    public function create(CrmConversionAttribution $attribution): void;

    public function findById(Uuid $id): ?CrmConversionAttribution;

    /**
     * @return array<CrmConversionAttribution>
     */
    public function findByContentId(Uuid $contentId): array;

    /**
     * @return array<CrmConversionAttribution>
     */
    public function findByOrganization(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    public function countByOrganizationAndType(Uuid $organizationId, AttributionType $type): int;

    public function sumValueByOrganization(Uuid $organizationId): float;
}
