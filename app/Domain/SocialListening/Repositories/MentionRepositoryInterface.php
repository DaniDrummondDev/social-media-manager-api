<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use DateTimeImmutable;

interface MentionRepositoryInterface
{
    public function create(Mention $mention): void;

    /**
     * @param  array<Mention>  $mentions
     */
    public function createBatch(array $mentions): void;

    public function findById(Uuid $id): ?Mention;

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<Mention>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array;

    /**
     * @return array<Mention>
     */
    public function findByQueryId(Uuid $queryId, ?string $cursor = null, int $limit = 20): array;

    public function countByQueryInPeriod(Uuid $queryId, DateTimeImmutable $from, DateTimeImmutable $to): int;

    public function existsByExternalId(string $externalId, string $platform): bool;

    public function update(Mention $mention): void;

    public function markAsRead(Uuid $id): void;

    /**
     * @param  array<string>  $ids
     */
    public function markManyAsRead(Uuid $organizationId, array $ids): void;

    /**
     * @return array<array<string, mixed>>
     */
    public function getSentimentTrend(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array;

    /**
     * @return array<array<string, mixed>>
     */
    public function getPlatformBreakdown(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array;

    /**
     * @return array<array<string, mixed>>
     */
    public function getTopAuthors(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null, int $limit = 10): array;

    public function countByOrganizationInPeriod(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): int;

    /**
     * @return array<string, int>
     */
    public function getSentimentCounts(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array;

    public function deleteOlderThan(DateTimeImmutable $before): int;
}
