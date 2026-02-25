<?php

declare(strict_types=1);

namespace App\Infrastructure\Billing\Repositories;

use App\Domain\Billing\Entities\UsageRecord;
use App\Domain\Billing\Repositories\UsageRecordRepositoryInterface;
use App\Domain\Billing\ValueObjects\UsageResourceType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Billing\Models\UsageRecordModel;
use DateTimeImmutable;

final class EloquentUsageRecordRepository implements UsageRecordRepositoryInterface
{
    public function __construct(
        private readonly UsageRecordModel $model,
    ) {}

    public function findByOrganizationAndResource(
        Uuid $organizationId,
        UsageResourceType $resourceType,
        DateTimeImmutable $periodStart,
    ): ?UsageRecord {
        /** @var UsageRecordModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('resource_type', $resourceType->value)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<UsageRecord>
     */
    public function findAllByOrganizationForPeriod(
        Uuid $organizationId,
        DateTimeImmutable $periodStart,
    ): array {
        /** @var \Illuminate\Database\Eloquent\Collection<int, UsageRecordModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->get();

        return $records->map(fn (UsageRecordModel $r) => $this->toDomain($r))->all();
    }

    public function createOrUpdate(UsageRecord $record): void
    {
        $this->model->newQuery()->updateOrCreate(
            [
                'organization_id' => (string) $record->organizationId,
                'resource_type' => $record->resourceType->value,
                'period_start' => $record->periodStart->format('Y-m-d'),
            ],
            [
                'id' => (string) $record->id,
                'quantity' => $record->quantity,
                'period_end' => $record->periodEnd->format('Y-m-d'),
                'recorded_at' => $record->recordedAt->format('Y-m-d H:i:s'),
            ],
        );
    }

    private function toDomain(UsageRecordModel $model): UsageRecord
    {
        $periodStart = $model->getAttribute('period_start');
        $periodEnd = $model->getAttribute('period_end');
        $recordedAt = $model->getAttribute('recorded_at');

        return UsageRecord::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            resourceType: UsageResourceType::from($model->getAttribute('resource_type')),
            quantity: (int) $model->getAttribute('quantity'),
            periodStart: new DateTimeImmutable($periodStart->format('Y-m-d')),
            periodEnd: new DateTimeImmutable($periodEnd->format('Y-m-d')),
            recordedAt: new DateTimeImmutable($recordedAt->format('Y-m-d H:i:s')),
        );
    }
}
