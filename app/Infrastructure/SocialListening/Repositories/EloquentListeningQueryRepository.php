<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningQuery;
use App\Domain\SocialListening\Repositories\ListeningQueryRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\QueryStatus;
use App\Domain\SocialListening\ValueObjects\QueryType;
use App\Infrastructure\SocialListening\Models\ListeningQueryModel;
use DateTimeImmutable;

final class EloquentListeningQueryRepository implements ListeningQueryRepositoryInterface
{
    public function __construct(
        private readonly ListeningQueryModel $model,
    ) {}

    public function create(ListeningQuery $query): void
    {
        $this->model->newQuery()->create($this->toArray($query));
    }

    public function update(ListeningQuery $query): void
    {
        $this->model->newQuery()
            ->where('id', (string) $query->id)
            ->update($this->toArray($query));
    }

    public function findById(Uuid $id): ?ListeningQuery
    {
        /** @var ListeningQueryModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<ListeningQuery>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningQueryModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ListeningQueryModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<ListeningQuery>
     */
    public function findActiveByPlatform(string $platform): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningQueryModel> $records */
        $records = $this->model->newQuery()
            ->where('status', 'active')
            ->whereJsonContains('platforms', $platform)
            ->get();

        return $records->map(fn (ListeningQueryModel $r) => $this->toDomain($r))->all();
    }

    public function countByOrganizationId(Uuid $organizationId): int
    {
        return (int) $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->count();
    }

    /**
     * @return array<string, array<string>>
     */
    public function findActiveGroupedByOrganization(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningQueryModel> $records */
        $records = $this->model->newQuery()
            ->where('status', 'active')
            ->get(['id', 'organization_id']);

        $grouped = [];
        foreach ($records as $record) {
            $orgId = $record->getAttribute('organization_id');
            $grouped[$orgId][] = $record->getAttribute('id');
        }

        return $grouped;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ListeningQuery $query): array
    {
        return [
            'id' => (string) $query->id,
            'organization_id' => (string) $query->organizationId,
            'name' => $query->name,
            'type' => $query->type->value,
            'value' => $query->value,
            'platforms' => $query->platforms,
            'status' => $query->status->value,
            'created_at' => $query->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $query->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ListeningQueryModel $model): ListeningQuery
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $platforms = $model->getAttribute('platforms');

        return ListeningQuery::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            type: QueryType::from($model->getAttribute('type')),
            value: $model->getAttribute('value'),
            platforms: is_array($platforms) ? $platforms : json_decode((string) $platforms, true),
            status: QueryStatus::from($model->getAttribute('status')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
