<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\CrmSyncLog;
use App\Domain\Engagement\Repositories\CrmSyncLogRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmEntityType;
use App\Domain\Engagement\ValueObjects\CrmSyncDirection;
use App\Domain\Engagement\ValueObjects\CrmSyncStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\CrmSyncLogModel;
use DateTimeImmutable;

final class EloquentCrmSyncLogRepository implements CrmSyncLogRepositoryInterface
{
    public function __construct(
        private readonly CrmSyncLogModel $model,
    ) {}

    public function create(CrmSyncLog $log): void
    {
        $this->model->newQuery()->create([
            'id' => (string) $log->id,
            'organization_id' => (string) $log->organizationId,
            'crm_connection_id' => (string) $log->connectionId,
            'direction' => $log->direction->value,
            'entity_type' => $log->entityType->value,
            'action' => $log->action,
            'status' => $log->status->value,
            'external_id' => $log->externalId,
            'error_message' => $log->errorMessage,
            'payload' => $log->payload,
            'created_at' => $log->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<CrmSyncLog>
     */
    public function findByConnectionId(Uuid $connectionId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('crm_connection_id', (string) $connectionId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmSyncLogModel> $records */
        $records = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $records->map(fn (CrmSyncLogModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<CrmSyncLog>
     */
    public function findFailed(Uuid $organizationId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('status', CrmSyncStatus::Failed->value);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmSyncLogModel> $records */
        $records = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $records->map(fn (CrmSyncLogModel $r) => $this->toDomain($r))->all();
    }

    public function countByConnectionAndPeriod(Uuid $connectionId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->model->newQuery()
            ->where('crm_connection_id', (string) $connectionId)
            ->where('created_at', '>=', $from->format('Y-m-d H:i:s'))
            ->where('created_at', '<=', $to->format('Y-m-d H:i:s'))
            ->count();
    }

    private function toDomain(CrmSyncLogModel $model): CrmSyncLog
    {
        $createdAt = $model->getAttribute('created_at');

        return CrmSyncLog::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            connectionId: Uuid::fromString($model->getAttribute('crm_connection_id')),
            direction: CrmSyncDirection::from($model->getAttribute('direction')),
            entityType: CrmEntityType::from($model->getAttribute('entity_type')),
            action: $model->getAttribute('action'),
            status: CrmSyncStatus::from($model->getAttribute('status')),
            externalId: $model->getAttribute('external_id'),
            errorMessage: $model->getAttribute('error_message'),
            payload: $model->getAttribute('payload'),
            createdAt: $createdAt instanceof \DateTimeInterface
                ? new DateTimeImmutable($createdAt->format('Y-m-d H:i:s'))
                : new DateTimeImmutable($createdAt),
        );
    }
}
