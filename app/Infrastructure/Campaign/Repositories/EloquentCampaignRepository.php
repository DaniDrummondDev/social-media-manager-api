<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Repositories;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Campaign\Models\CampaignModel;
use DateTimeImmutable;

final class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function __construct(
        private readonly CampaignModel $model,
    ) {}

    public function create(Campaign $campaign): void
    {
        $this->model->newQuery()->create($this->toArray($campaign));
    }

    public function update(Campaign $campaign): void
    {
        $this->model->newQuery()
            ->where('id', (string) $campaign->id)
            ->update($this->toArray($campaign));
    }

    public function findById(Uuid $id): ?Campaign
    {
        /** @var CampaignModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return Campaign[]
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, CampaignModel> $records */
        return $records->map(fn (CampaignModel $record) => $this->toDomain($record))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    public function existsByOrganizationAndName(Uuid $organizationId, string $name, ?Uuid $excludeId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->whereNull('deleted_at');

        if ($excludeId !== null) {
            $query->where('id', '!=', (string) $excludeId);
        }

        return $query->exists();
    }

    private function toDomain(CampaignModel $model): Campaign
    {
        return Campaign::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            createdBy: Uuid::fromString($model->getAttribute('created_by')),
            name: $model->getAttribute('name'),
            description: $model->getAttribute('description'),
            startsAt: $model->getAttribute('starts_at')
                ? new DateTimeImmutable($model->getAttribute('starts_at')->toDateTimeString())
                : null,
            endsAt: $model->getAttribute('ends_at')
                ? new DateTimeImmutable($model->getAttribute('ends_at')->toDateTimeString())
                : null,
            status: CampaignStatus::from($model->getAttribute('status')),
            tags: $model->getAttribute('tags') ?? [],
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
            deletedAt: $model->getAttribute('deleted_at')
                ? new DateTimeImmutable($model->getAttribute('deleted_at')->toDateTimeString())
                : null,
            purgeAt: $model->getAttribute('purge_at')
                ? new DateTimeImmutable($model->getAttribute('purge_at')->toDateTimeString())
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Campaign $campaign): array
    {
        return [
            'id' => (string) $campaign->id,
            'organization_id' => (string) $campaign->organizationId,
            'created_by' => (string) $campaign->createdBy,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'starts_at' => $campaign->startsAt?->format('Y-m-d H:i:s'),
            'ends_at' => $campaign->endsAt?->format('Y-m-d H:i:s'),
            'status' => $campaign->status->value,
            'tags' => $campaign->tags,
            'deleted_at' => $campaign->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => $campaign->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }
}
