<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\Audience;
use App\Domain\PaidAdvertising\Repositories\AudienceRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\TargetingSpec;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PaidAdvertising\Models\AudienceModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

final class EloquentAudienceRepository implements AudienceRepositoryInterface
{
    public function __construct(
        private readonly AudienceModel $model,
    ) {}

    public function create(Audience $audience): void
    {
        $this->model->newQuery()->create($this->toArray($audience));
    }

    public function update(Audience $audience): void
    {
        $this->model->newQuery()
            ->where('id', (string) $audience->id)
            ->update($this->toArray($audience));
    }

    public function findById(Uuid $id): ?Audience
    {
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<Audience>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    public function existsByNameAndOrganization(string $name, Uuid $organizationId): bool
    {
        return $this->model->newQuery()
            ->where('name', $name)
            ->where('organization_id', (string) $organizationId)
            ->exists();
    }

    private function toDomain(Model $model): Audience
    {
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return Audience::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            name: $model->getAttribute('name'),
            targetingSpec: TargetingSpec::fromArray($model->getAttribute('targeting_spec') ?? []),
            providerAudienceIds: $model->getAttribute('provider_audience_ids'),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Audience $audience): array
    {
        return [
            'id' => (string) $audience->id,
            'organization_id' => (string) $audience->organizationId,
            'name' => $audience->name,
            'targeting_spec' => $audience->targetingSpec->toArray(),
            'provider_audience_ids' => $audience->providerAudienceIds,
        ];
    }
}
