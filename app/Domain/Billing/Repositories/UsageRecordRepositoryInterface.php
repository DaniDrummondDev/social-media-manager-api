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
     * @return array<UsageRecord>
     */
    public function findAllByOrganizationForPeriod(
        Uuid $organizationId,
        DateTimeImmutable $periodStart,
    ): array;

    public function createOrUpdate(UsageRecord $record): void;
}
