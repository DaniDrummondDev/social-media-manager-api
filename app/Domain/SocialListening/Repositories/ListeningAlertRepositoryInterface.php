<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;

interface ListeningAlertRepositoryInterface
{
    public function create(ListeningAlert $alert): void;

    public function update(ListeningAlert $alert): void;

    public function findById(Uuid $id): ?ListeningAlert;

    /**
     * @return array{items: array<ListeningAlert>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<ListeningAlert>
     */
    public function findActiveByQueryId(string $queryId): array;

    /**
     * @return array<ListeningAlert>
     */
    public function findAllActive(): array;

    public function delete(Uuid $id): void;
}
