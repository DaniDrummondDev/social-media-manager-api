<?php

declare(strict_types=1);

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface UsageRecordRepositoryInterface
{
    public function findByOrganizationAndResource(
        Uuid $organizationId,
        UsageResourceType $resourceType,
        DateTimeImmutable $periodStart,
    ): ?UsageRecord;

    /**
     * Find with SELECT ... FOR UPDATE lock for atomic read-then-write.
     */
    public function findByOrganizationAndResourceForUpdate(
        Uuid $organizationId,
        UsageResourceType $resourceType,
        DateTimeImmutable $periodStart,
    ): ?UsageRecord;

    /**
     * @return array<UsageRecord>
     */
    public function findAllByOrganizationForPeriod(
        Uuid $organizationId,
        DateTimeImmutable $periodStart,
    ): array;

    public function createOrUpdate(UsageRecord $record): void;

    /**
     * Atomically increment (or create) a usage record within a transaction.
     */
    public function incrementOrCreate(
        Uuid $organizationId,
        UsageResourceType $resourceType,
        int $amount,
        DateTimeImmutable $periodStart,
        DateTimeImmutable $periodEnd,
    ): void;
}
