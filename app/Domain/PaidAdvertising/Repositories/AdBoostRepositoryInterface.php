<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdBoost;
use App\Domain\PaidAdvertising\ValueObjects\AdStatus;
use App\Domain\Shared\ValueObjects\Uuid;

interface AdBoostRepositoryInterface
{
    public function create(AdBoost $boost): void;

    public function update(AdBoost $boost): void;

    public function findById(Uuid $id): ?AdBoost;

    /**
     * @return array<AdBoost>
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<AdBoost>
     */
    public function findByScheduledPostId(Uuid $scheduledPostId): array;

    /**
     * @return array<AdBoost>
     */
    public function findByStatus(AdStatus $status): array;

    /**
     * @return array<AdBoost>
     */
    public function findActiveByAdAccountId(Uuid $adAccountId): array;

    public function delete(Uuid $id): void;
}
