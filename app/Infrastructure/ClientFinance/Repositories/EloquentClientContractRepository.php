<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\ClientContract;
use App\Domain\ClientFinance\Repositories\ClientContractRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\ContractStatus;
use App\Domain\ClientFinance\ValueObjects\ContractType;
use App\Domain\ClientFinance\ValueObjects\Currency;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ClientFinance\Models\ClientContractModel;
use DateTimeImmutable;

final class EloquentClientContractRepository implements ClientContractRepositoryInterface
{
    public function __construct(
        private readonly ClientContractModel $model,
    ) {}

    public function findById(Uuid $id): ?ClientContract
    {
        /** @var ClientContractModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?ClientContract
    {
        /** @var ClientContractModel|null $record */
        $record = $this->model->newQuery()
            ->where('id', (string) $id)
            ->where('organization_id', (string) $organizationId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<ClientContract>, next_cursor: ?string}
     */
    public function findByClient(
        Uuid $clientId,
        Uuid $organizationId,
        ?string $status = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->where('client_id', (string) $clientId)
            ->where('organization_id', (string) $organizationId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $query->orderBy('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientContractModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ClientContractModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<ClientContract>
     */
    public function findActiveByOrganization(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientContractModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('status', 'active')
            ->get();

        return $records->map(fn (ClientContractModel $r) => $this->toDomain($r))->all();
    }

    public function create(ClientContract $contract): void
    {
        $this->model->newQuery()->create($this->toArray($contract));
    }

    public function update(ClientContract $contract): void
    {
        $this->model->newQuery()
            ->where('id', (string) $contract->id)
            ->update($this->toArray($contract));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ClientContract $contract): array
    {
        return [
            'id' => (string) $contract->id,
            'client_id' => (string) $contract->clientId,
            'organization_id' => (string) $contract->organizationId,
            'name' => $contract->name,
            'type' => $contract->type->value,
            'value_cents' => $contract->valueCents,
            'currency' => $contract->currency->value,
            'starts_at' => $contract->startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $contract->endsAt?->format('Y-m-d H:i:s'),
            'social_account_ids' => $contract->socialAccountIds,
            'status' => $contract->status->value,
        ];
    }

    private function toDomain(ClientContractModel $model): ClientContract
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $startsAt = $model->getAttribute('starts_at');
        $endsAt = $model->getAttribute('ends_at');
        $socialAccountIds = $model->getAttribute('social_account_ids');

        return ClientContract::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            clientId: Uuid::fromString($model->getAttribute('client_id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            type: ContractType::from($model->getAttribute('type')),
            valueCents: (int) $model->getAttribute('value_cents'),
            currency: Currency::from($model->getAttribute('currency')),
            startsAt: new DateTimeImmutable($startsAt->format('Y-m-d H:i:s')),
            endsAt: $endsAt ? new DateTimeImmutable($endsAt->format('Y-m-d H:i:s')) : null,
            socialAccountIds: is_array($socialAccountIds) ? $socialAccountIds : [],
            status: ContractStatus::from($model->getAttribute('status')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }
}
