<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\CostAllocation;
use App\Domain\ClientFinance\Repositories\CostAllocationRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\ClientFinance\ValueObjects\ResourceType;
use App\Domain\ClientFinance\ValueObjects\YearMonth;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ClientFinance\Models\CostAllocationModel;
use DateTimeImmutable;

final class EloquentCostAllocationRepository implements CostAllocationRepositoryInterface
{
    public function __construct(
        private readonly CostAllocationModel $model,
    ) {}

    public function findById(Uuid $id): ?CostAllocation
    {
        /** @var CostAllocationModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<CostAllocation>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $clientId = null,
        ?string $resourceType = null,
        ?string $from = null,
        ?string $to = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($clientId !== null) {
            $query->where('client_id', $clientId);
        }

        if ($resourceType !== null) {
            $query->where('resource_type', $resourceType);
        }

        if ($from !== null) {
            $query->where('allocated_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('allocated_at', '<=', $to);
        }

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $query->orderBy('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, CostAllocationModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (CostAllocationModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    public function create(CostAllocation $allocation): void
    {
        $this->model->newQuery()->create($this->toArray($allocation));
    }

    public function sumByClient(Uuid $clientId, Uuid $organizationId, ?YearMonth $month = null): int
    {
        $query = $this->model->newQuery()
            ->where('client_id', (string) $clientId)
            ->where('organization_id', (string) $organizationId);

        if ($month !== null) {
            $query->where('allocated_at', '>=', $month->startOfMonth()->format('Y-m-d H:i:s'))
                ->where('allocated_at', '<=', $month->endOfMonth()->format('Y-m-d H:i:s'));
        }

        return (int) $query->sum('cost_cents');
    }

    public function sumByOrganization(Uuid $organizationId, ?YearMonth $month = null): int
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($month !== null) {
            $query->where('allocated_at', '>=', $month->startOfMonth()->format('Y-m-d H:i:s'))
                ->where('allocated_at', '<=', $month->endOfMonth()->format('Y-m-d H:i:s'));
        }

        return (int) $query->sum('cost_cents');
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(CostAllocation $allocation): array
    {
        return [
            'id' => (string) $allocation->id,
            'client_id' => (string) $allocation->clientId,
            'organization_id' => (string) $allocation->organizationId,
            'resource_type' => $allocation->resourceType->value,
            'resource_id' => $allocation->resourceId ? (string) $allocation->resourceId : null,
            'description' => $allocation->description,
            'cost_cents' => $allocation->costCents,
            'currency' => $allocation->currency->value,
            'allocated_at' => $allocation->allocatedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(CostAllocationModel $model): CostAllocation
    {
        $createdAt = $model->getAttribute('created_at');
        $allocatedAt = $model->getAttribute('allocated_at');
        $resourceId = $model->getAttribute('resource_id');

        return CostAllocation::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            clientId: Uuid::fromString($model->getAttribute('client_id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            resourceType: ResourceType::from($model->getAttribute('resource_type')),
            resourceId: $resourceId !== null ? Uuid::fromString($resourceId) : null,
            description: $model->getAttribute('description'),
            costCents: (int) $model->getAttribute('cost_cents'),
            currency: Currency::from($model->getAttribute('currency')),
            allocatedAt: new DateTimeImmutable($allocatedAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
        );
    }
}
