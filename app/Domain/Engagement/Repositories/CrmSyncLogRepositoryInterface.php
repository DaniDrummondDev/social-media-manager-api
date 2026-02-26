<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Repositories;

use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

interface CrmSyncLogRepositoryInterface
{
    public function create(CrmSyncLog $log): void;

    /**
     * @return array<CrmSyncLog>
     */
    public function findByConnectionId(Uuid $connectionId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<CrmSyncLog>
     */
    public function findFailed(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    public function countByConnectionAndPeriod(Uuid $connectionId, DateTimeImmutable $from, DateTimeImmutable $to): int;
}
