<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\CostAllocation;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;

interface CostAllocationRepositoryInterface
{
    public function findById(Uuid $id): ?CostAllocation;

    /**
     * @return array{items: array<CostAllocation>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $clientId = null,
        ?string $resourceType = null,
        ?string $from = null,
        ?string $to = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array;

    public function create(CostAllocation $allocation): void;

    public function sumByClient(Uuid $clientId, Uuid $organizationId, ?YearMonth $month = null): int;

    public function sumByOrganization(Uuid $organizationId, ?YearMonth $month = null): int;
}
