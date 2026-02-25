<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\Client;
use App\Domain\Shared\ValueObjects\Uuid;

interface ClientRepositoryInterface
{
    public function findById(Uuid $id): ?Client;

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?Client;

    /**
     * @return array{items: array<Client>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $status = null,
        ?string $search = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array;

    public function create(Client $client): void;

    public function update(Client $client): void;
}
