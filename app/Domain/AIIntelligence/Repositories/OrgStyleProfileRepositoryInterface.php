<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Repositories;

use App\Domain\AIIntelligence\Entities\OrgStyleProfile;
use App\Domain\Shared\ValueObjects\Uuid;

interface OrgStyleProfileRepositoryInterface
{
    public function save(OrgStyleProfile $profile): void;

    public function findById(Uuid $id): ?OrgStyleProfile;

    /**
     * Find active (non-expired) profile for a specific organization and generation type.
     * Unique constraint: (organization_id, generation_type).
     */
    public function findActiveByOrganizationAndType(
        Uuid $organizationId,
        string $generationType,
    ): ?OrgStyleProfile;

    /**
     * @return array<OrgStyleProfile>
     */
    public function findActiveByOrganization(Uuid $organizationId): array;

    /**
     * @return array<OrgStyleProfile>
     */
    public function findExpired(): array;
}
