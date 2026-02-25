<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\ClientContract;
use App\Domain\Shared\ValueObjects\Uuid;

interface ClientContractRepositoryInterface
{
    public function findById(Uuid $id): ?ClientContract;

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?ClientContract;

    /**
     * @return array{items: array<ClientContract>, next_cursor: ?string}
     */
    public function findByClient(
        Uuid $clientId,
        Uuid $organizationId,
        ?string $status = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array;

    /**
     * @return array<ClientContract>
     */
    public function findActiveByOrganization(Uuid $organizationId): array;

    public function create(ClientContract $contract): void;

    public function update(ClientContract $contract): void;
}
