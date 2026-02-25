<?php

declare(strict_types=1);

namespace App\Infrastructure\ClientFinance\Repositories;

use App\Domain\ClientFinance\Entities\Client;
use App\Domain\ClientFinance\Repositories\ClientRepositoryInterface;
use App\Domain\ClientFinance\ValueObjects\Address;
use App\Domain\ClientFinance\ValueObjects\ClientStatus;
use App\Domain\ClientFinance\ValueObjects\TaxId;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\ClientFinance\Models\ClientModel;
use DateTimeImmutable;

final class EloquentClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly ClientModel $model,
    ) {}

    public function findById(Uuid $id): ?Client
    {
        /** @var ClientModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByIdAndOrganization(Uuid $id, Uuid $organizationId): ?Client
    {
        /** @var ClientModel|null $record */
        $record = $this->model->newQuery()
            ->where('id', (string) $id)
            ->where('organization_id', (string) $organizationId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array{items: array<Client>, next_cursor: ?string}
     */
    public function findByOrganization(
        Uuid $organizationId,
        ?string $status = null,
        ?string $search = null,
        ?string $cursor = null,
        int $limit = 20,
    ): array {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('company_name', 'ilike', "%{$search}%");
            });
        }

        if ($cursor !== null) {
            $query->where('id', '>', $cursor);
        }

        $query->orderBy('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ClientModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (ClientModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    public function create(Client $client): void
    {
        $this->model->newQuery()->create($this->toArray($client));
    }

    public function update(Client $client): void
    {
        $this->model->newQuery()
            ->where('id', (string) $client->id)
            ->update($this->toArray($client));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Client $client): array
    {
        return [
            'id' => (string) $client->id,
            'organization_id' => (string) $client->organizationId,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company_name' => $client->companyName,
            'tax_id' => $client->taxId?->value,
            'tax_id_type' => $client->taxId?->type,
            'billing_address' => $client->billingAddress?->toArray(),
            'notes' => $client->notes,
            'status' => $client->status->value,
            'purge_at' => $client->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(ClientModel $model): Client
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $deletedAt = $model->getAttribute('deleted_at');
        $purgeAt = $model->getAttribute('purge_at');
        $taxId = $model->getAttribute('tax_id');
        $billingAddress = $model->getAttribute('billing_address');

        return Client::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            email: $model->getAttribute('email'),
            phone: $model->getAttribute('phone'),
            companyName: $model->getAttribute('company_name'),
            taxId: $taxId !== null ? TaxId::fromString($taxId) : null,
            billingAddress: $billingAddress !== null ? Address::fromArray($billingAddress) : null,
            notes: $model->getAttribute('notes'),
            status: ClientStatus::from($model->getAttribute('status')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
            deletedAt: $deletedAt ? new DateTimeImmutable($deletedAt->format('Y-m-d H:i:s')) : null,
            purgeAt: $purgeAt ? new DateTimeImmutable($purgeAt->format('Y-m-d H:i:s')) : null,
        );
    }
}
