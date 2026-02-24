<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Contracts;

use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Shared\ValueObjects\Uuid;

interface CampaignRepositoryInterface
{
    public function create(Campaign $campaign): void;

    public function update(Campaign $campaign): void;

    public function findById(Uuid $id): ?Campaign;

    /**
     * @return Campaign[]
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    public function delete(Uuid $id): void;

    public function existsByOrganizationAndName(Uuid $organizationId, string $name, ?Uuid $excludeId = null): bool;
}
