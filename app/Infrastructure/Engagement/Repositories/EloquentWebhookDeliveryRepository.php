<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\WebhookDelivery;
use App\Domain\Engagement\Repositories\WebhookDeliveryRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\WebhookDeliveryModel;
use DateTimeImmutable;

final class EloquentWebhookDeliveryRepository implements WebhookDeliveryRepositoryInterface
{
    public function __construct(
        private readonly WebhookDeliveryModel $model,
    ) {}

    public function create(WebhookDelivery $delivery): void
    {
        $this->model->newQuery()->create($this->toArray($delivery));
    }

    public function update(WebhookDelivery $delivery): void
    {
        $this->model->newQuery()
            ->where('id', (string) $delivery->id)
            ->update($this->toArray($delivery));
    }

    public function findById(Uuid $id): ?WebhookDelivery
    {
        /** @var WebhookDeliveryModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<WebhookDelivery>
     */
    public function findByEndpointId(Uuid $endpointId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('webhook_endpoint_id', (string) $endpointId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, WebhookDeliveryModel> $records */
        $records = $query->orderByDesc('id')->limit($limit)->get();

        return $records->map(fn (WebhookDeliveryModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<WebhookDelivery>
     */
    public function findPendingRetries(DateTimeImmutable $now): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, WebhookDeliveryModel> $records */
        $records = $this->model->newQuery()
            ->where('next_retry_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNull('delivered_at')
            ->whereNull('failed_at')
            ->limit(100)
            ->get();

        return $records->map(fn (WebhookDeliveryModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WebhookDelivery $delivery): array
    {
        return [
            'id' => (string) $delivery->id,
            'webhook_endpoint_id' => (string) $delivery->webhookEndpointId,
            'event' => $delivery->event,
            'payload' => $delivery->payload,
            'response_status' => $delivery->responseStatus,
            'response_body' => $delivery->responseBody,
            'response_time_ms' => $delivery->responseTimeMs,
            'attempts' => $delivery->attempts,
            'max_attempts' => $delivery->maxAttempts,
            'next_retry_at' => $delivery->nextRetryAt?->format('Y-m-d H:i:s'),
            'delivered_at' => $delivery->deliveredAt?->format('Y-m-d H:i:s'),
            'failed_at' => $delivery->failedAt?->format('Y-m-d H:i:s'),
            'created_at' => $delivery->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(WebhookDeliveryModel $model): WebhookDelivery
    {
        $nextRetryAt = $model->getAttribute('next_retry_at');
        $deliveredAt = $model->getAttribute('delivered_at');
        $failedAt = $model->getAttribute('failed_at');
        $createdAt = $model->getAttribute('created_at');

        return WebhookDelivery::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            webhookEndpointId: Uuid::fromString($model->getAttribute('webhook_endpoint_id')),
            event: $model->getAttribute('event'),
            payload: $model->getAttribute('payload') ?? [],
            responseStatus: $model->getAttribute('response_status'),
            responseBody: $model->getAttribute('response_body'),
            responseTimeMs: $model->getAttribute('response_time_ms'),
            attempts: (int) $model->getAttribute('attempts'),
            maxAttempts: (int) $model->getAttribute('max_attempts'),
            nextRetryAt: $nextRetryAt ? new DateTimeImmutable($nextRetryAt->format('Y-m-d H:i:s')) : null,
            deliveredAt: $deliveredAt ? new DateTimeImmutable($deliveredAt->format('Y-m-d H:i:s')) : null,
            failedAt: $failedAt ? new DateTimeImmutable($failedAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
