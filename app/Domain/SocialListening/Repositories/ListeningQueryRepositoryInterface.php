<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;

interface ListeningQueryRepositoryInterface
{
    public function create(ListeningQuery $query): void;

    public function update(ListeningQuery $query): void;

    public function findById(Uuid $id): ?ListeningQuery;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<ListeningQuery>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<ListeningQuery>
     */
    public function findActiveByPlatform(string $platform): array;

    public function countByOrganizationId(Uuid $organizationId): int;

    /**
     * @return array<string, array<string>>  Map of organizationId => array of queryIds
     */
    public function findActiveGroupedByOrganization(): array;

    public function delete(Uuid $id): void;
}
