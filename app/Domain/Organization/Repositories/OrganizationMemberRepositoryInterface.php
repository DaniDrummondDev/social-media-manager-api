<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repositories;

use App\Domain\Organization\Entities\OrganizationMember;
use App\Domain\Shared\ValueObjects\Uuid;

interface OrganizationMemberRepositoryInterface
{
    public function create(OrganizationMember $member): void;

    public function update(OrganizationMember $member): void;

    public function findByOrgAndUser(Uuid $organizationId, Uuid $userId): ?OrganizationMember;

    /** @return OrganizationMember[] */
    public function listByOrganization(Uuid $organizationId): array;

    public function delete(Uuid $organizationId, Uuid $userId): void;

    public function countByOrganization(Uuid $organizationId): int;
}
