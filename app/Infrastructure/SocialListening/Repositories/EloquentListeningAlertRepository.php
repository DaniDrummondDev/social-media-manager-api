<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningAlert;
use App\Domain\SocialListening\Repositories\ListeningAlertRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\AlertCondition;
use App\Domain\SocialListening\ValueObjects\NotificationChannel;
use App\Infrastructure\SocialListening\Models\ListeningAlertModel;
use DateTimeImmutable;

final class EloquentListeningAlertRepository implements ListeningAlertRepositoryInterface
{
    public function __construct(
        private readonly ListeningAlertModel $model,
    ) {}

    public function create(ListeningAlert $alert): void
    {
        $this->model->newQuery()->create($this->toArray($alert));
    }

    public function update(ListeningAlert $alert): void
    {
        $this->model->newQuery()
            ->where('id', (string) $alert->id)
            ->update($this->toArray($alert));
    }

    public function findById(Uuid $id): ?ListeningAlert
    {
        /** @var ListeningAlertModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<ListeningAlert>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningAlertModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ListeningAlertModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<ListeningAlert>
     */
    public function findActiveByQueryId(string $queryId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningAlertModel> $records */
        $records = $this->model->newQuery()
            ->where('is_active', true)
            ->whereJsonContains('query_ids', $queryId)
            ->get();

        return $records->map(fn (ListeningAlertModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<ListeningAlert>
     */
    public function findAllActive(): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ListeningAlertModel> $records */
        $records = $this->model->newQuery()
            ->where('is_active', true)
            ->get();

        return $records->map(fn (ListeningAlertModel $r) => $this->toDomain($r))->all();
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
    private function toArray(ListeningAlert $alert): array
    {
        return [
            'id' => (string) $alert->id,
            'organization_id' => (string) $alert->organizationId,
            'name' => $alert->name,
            'query_ids' => $alert->queryIds,
            'condition_type' => $alert->condition->type->value,
            'threshold' => $alert->condition->threshold,
            'window_minutes' => $alert->condition->windowMinutes,
            'channels' => array_map(fn (NotificationChannel $ch) => $ch->value, $alert->channels),
            'cooldown_minutes' => $alert->cooldownMinutes,
            'is_active' => $alert->isActive,
            'last_triggered_at' => $alert->lastTriggeredAt?->format('Y-m-d H:i:s'),
            'created_at' => $alert->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $alert->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ListeningAlertModel $model): ListeningAlert
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $lastTriggeredAt = $model->getAttribute('last_triggered_at');
        $queryIds = $model->getAttribute('query_ids');
        $channels = $model->getAttribute('channels');

        $queryIdsArray = is_array($queryIds) ? $queryIds : json_decode((string) $queryIds, true);
        $channelsArray = is_array($channels) ? $channels : json_decode((string) $channels, true);

        $condition = AlertCondition::fromArray([
            'type' => $model->getAttribute('condition_type'),
            'threshold' => (int) $model->getAttribute('threshold'),
            'window_minutes' => (int) $model->getAttribute('window_minutes'),
        ]);

        $channelEnums = array_map(
            fn (string $ch) => NotificationChannel::from($ch),
            $channelsArray,
        );

        return ListeningAlert::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            queryIds: $queryIdsArray,
            condition: $condition,
            channels: $channelEnums,
            cooldownMinutes: (int) $model->getAttribute('cooldown_minutes'),
            isActive: (bool) $model->getAttribute('is_active'),
            lastTriggeredAt: $lastTriggeredAt ? new DateTimeImmutable($lastTriggeredAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
