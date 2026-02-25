<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\WebhookEndpoint;
use App\Domain\Engagement\Repositories\WebhookEndpointRepositoryInterface;
use App\Domain\Engagement\ValueObjects\WebhookSecret;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\WebhookEndpointModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentWebhookEndpointRepository implements WebhookEndpointRepositoryInterface
{
    public function __construct(
        private readonly WebhookEndpointModel $model,
    ) {}

    public function create(WebhookEndpoint $endpoint): void
    {
        $this->model->newQuery()->create($this->toArray($endpoint));
    }

    public function update(WebhookEndpoint $endpoint): void
    {
        $this->model->newQuery()
            ->where('id', (string) $endpoint->id)
            ->update($this->toArray($endpoint));
    }

    public function findById(Uuid $id): ?WebhookEndpoint
    {
        /** @var WebhookEndpointModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<WebhookEndpoint>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, WebhookEndpointModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (WebhookEndpointModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<WebhookEndpoint>
     */
    public function findSubscribedToEvent(Uuid $organizationId, string $event): array
    {
        $driver = DB::getDriverName();

        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($driver === 'pgsql') {
            $query->whereRaw('events @> ?', [json_encode([$event])]);
        } else {
            $query->whereRaw("json_extract(events, '$') LIKE ?", ['%"'.$event.'"%']);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, WebhookEndpointModel> $records */
        $records = $query->get();

        return $records->map(fn (WebhookEndpointModel $r) => $this->toDomain($r))->all();
    }

    public function countByOrganization(Uuid $organizationId): int
    {
        return (int) $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WebhookEndpoint $endpoint): array
    {
        return [
            'id' => (string) $endpoint->id,
            'organization_id' => (string) $endpoint->organizationId,
            'name' => $endpoint->name,
            'url' => $endpoint->url,
            'secret' => (string) $endpoint->secret,
            'events' => $endpoint->events,
            'headers' => $endpoint->headers,
            'is_active' => $endpoint->isActive,
            'last_delivery_at' => $endpoint->lastDeliveryAt?->format('Y-m-d H:i:s'),
            'last_delivery_status' => $endpoint->lastDeliveryStatus,
            'failure_count' => $endpoint->failureCount,
            'deleted_at' => $endpoint->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => $endpoint->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(WebhookEndpointModel $model): WebhookEndpoint
    {
        $lastDeliveryAt = $model->getAttribute('last_delivery_at');
        $deletedAt = $model->getAttribute('deleted_at');
        $purgeAt = $model->getAttribute('purge_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return WebhookEndpoint::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            url: $model->getAttribute('url'),
            secret: WebhookSecret::fromString($model->getAttribute('secret')),
            events: $model->getAttribute('events') ?? [],
            headers: $model->getAttribute('headers'),
            isActive: (bool) $model->getAttribute('is_active'),
            lastDeliveryAt: $lastDeliveryAt ? new DateTimeImmutable($lastDeliveryAt->format('Y-m-d H:i:s')) : null,
            lastDeliveryStatus: $model->getAttribute('last_delivery_status'),
            failureCount: (int) $model->getAttribute('failure_count'),
            deletedAt: $deletedAt ? new DateTimeImmutable($deletedAt->format('Y-m-d H:i:s')) : null,
            purgeAt: $purgeAt ? new DateTimeImmutable($purgeAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
