<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

interface CrmConnectionRepositoryInterface
{
    public function create(CrmConnection $connection): void;

    public function update(CrmConnection $connection): void;

    public function findById(Uuid $id): ?CrmConnection;

    /**
     * @return array<CrmConnection>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    public function findByOrganizationAndProvider(Uuid $organizationId, CrmProvider $provider): ?CrmConnection;

    /**
     * Find connections with tokens expiring within the given number of minutes.
     *
     * @return array<CrmConnection>
     */
    public function findExpiringTokens(int $minutesUntilExpiry): array;

    public function delete(Uuid $id): void;
}
