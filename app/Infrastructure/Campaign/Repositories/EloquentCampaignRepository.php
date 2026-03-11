<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Repositories;

use App\Domain\Campaign\Contracts\CampaignRepositoryInterface;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\ValueObjects\CampaignBrief;
use App\Domain\Campaign\ValueObjects\CampaignStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Campaign\Models\CampaignModel;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 600; // 10 minutes

    public function __construct(
        private readonly CampaignModel $model,
        private readonly CacheRepository $cache,
    ) {}

    public function create(Campaign $campaign): void
    {
        $this->model->newQuery()->create($this->toArray($campaign));
        $this->invalidateCache($campaign->organizationId);
    }

    public function update(Campaign $campaign): void
    {
        $this->model->newQuery()
            ->where('id', (string) $campaign->id)
            ->update($this->toArray($campaign));
        $this->invalidateCache($campaign->organizationId);
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
    public function findByOrganizationId(Uuid $organizationId, int $limit = 100): array
    {
        $cacheKey = $this->getCacheKey($organizationId);

        /** @var array<array<string, mixed>>|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $campaigns = array_map(
                fn (array $data) => $this->toDomainFromCached($data),
                $cached
            );

            return array_slice($campaigns, 0, $limit);
        }

        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, CampaignModel> $records */
        $campaigns = $records->map(fn (CampaignModel $record) => $this->toDomain($record))->all();

        // Cache raw data for serialization
        $cacheData = $records->map(fn (CampaignModel $r) => $r->toArray())->all();
        $this->cache->put($cacheKey, $cacheData, self::CACHE_TTL_SECONDS);

        return $campaigns;
    }

    public function delete(Uuid $id): void
    {
        // Get campaign first to invalidate cache for its organization
        /** @var CampaignModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();

        if ($record !== null) {
            $this->invalidateCache(Uuid::fromString($record->getAttribute('organization_id')));
        }
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
        $brief = null;
        if ($model->getAttribute('brief_text') !== null
            || $model->getAttribute('brief_target_audience') !== null
            || $model->getAttribute('brief_restrictions') !== null
            || $model->getAttribute('brief_cta') !== null
        ) {
            $brief = new CampaignBrief(
                text: $model->getAttribute('brief_text'),
                targetAudience: $model->getAttribute('brief_target_audience'),
                restrictions: $model->getAttribute('brief_restrictions'),
                cta: $model->getAttribute('brief_cta'),
            );
        }

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
            brief: $brief,
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
            'brief_text' => $campaign->brief?->text,
            'brief_target_audience' => $campaign->brief?->targetAudience,
            'brief_restrictions' => $campaign->brief?->restrictions,
            'brief_cta' => $campaign->brief?->cta,
        ];
    }

    private function getCacheKey(Uuid $organizationId): string
    {
        return "campaigns:org:{$organizationId}";
    }

    private function invalidateCache(Uuid $organizationId): void
    {
        $this->cache->forget($this->getCacheKey($organizationId));
    }

    /**
     * Reconstitute domain object from cached array data.
     *
     * @param  array<string, mixed>  $data
     */
    private function toDomainFromCached(array $data): Campaign
    {
        $brief = null;
        if (($data['brief_text'] ?? null) !== null
            || ($data['brief_target_audience'] ?? null) !== null
            || ($data['brief_restrictions'] ?? null) !== null
            || ($data['brief_cta'] ?? null) !== null
        ) {
            $brief = new CampaignBrief(
                text: $data['brief_text'] ?? null,
                targetAudience: $data['brief_target_audience'] ?? null,
                restrictions: $data['brief_restrictions'] ?? null,
                cta: $data['brief_cta'] ?? null,
            );
        }

        return Campaign::reconstitute(
            id: Uuid::fromString($data['id']),
            organizationId: Uuid::fromString($data['organization_id']),
            createdBy: Uuid::fromString($data['created_by']),
            name: $data['name'],
            description: $data['description'] ?? null,
            startsAt: isset($data['starts_at'])
                ? new DateTimeImmutable($data['starts_at'])
                : null,
            endsAt: isset($data['ends_at'])
                ? new DateTimeImmutable($data['ends_at'])
                : null,
            status: CampaignStatus::from($data['status']),
            tags: $data['tags'] ?? [],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            deletedAt: isset($data['deleted_at'])
                ? new DateTimeImmutable($data['deleted_at'])
                : null,
            purgeAt: isset($data['purge_at'])
                ? new DateTimeImmutable($data['purge_at'])
                : null,
            brief: $brief,
        );
    }
}
