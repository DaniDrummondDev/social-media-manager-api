<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Repositories;

use App\Domain\Campaign\Contracts\ContentRepositoryInterface;
use App\Domain\Campaign\Entities\Content;
use App\Domain\Campaign\ValueObjects\ContentStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Campaign\Models\ContentModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentContentRepository implements ContentRepositoryInterface
{
    public function __construct(
        private readonly ContentModel $model,
    ) {}

    public function create(Content $content): void
    {
        $this->model->newQuery()->create($this->toArray($content));
    }

    public function update(Content $content): void
    {
        $this->model->newQuery()
            ->where('id', (string) $content->id)
            ->update($this->toArray($content));
    }

    public function findById(Uuid $id): ?Content
    {
        /** @var ContentModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return Content[]
     */
    public function findByCampaignId(Uuid $campaignId, int $limit = 500): array
    {
        $records = $this->model->newQuery()
            ->where('campaign_id', (string) $campaignId)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ContentModel> $records */
        return $records->map(fn (ContentModel $record) => $this->toDomain($record))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return array<string, int>
     */
    public function countByCampaignAndStatus(Uuid $campaignId): array
    {
        $results = DB::table('contents')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('campaign_id', (string) $campaignId)
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row->status] = (int) $row->total;
        }

        return $counts;
    }

    private function toDomain(ContentModel $model): Content
    {
        return Content::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            campaignId: Uuid::fromString($model->getAttribute('campaign_id')),
            createdBy: Uuid::fromString($model->getAttribute('created_by')),
            title: $model->getAttribute('title'),
            body: $model->getAttribute('body'),
            hashtags: $model->getAttribute('hashtags') ?? [],
            status: ContentStatus::from($model->getAttribute('status')),
            aiGenerationId: $model->getAttribute('ai_generation_id'),
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
    private function toArray(Content $content): array
    {
        return [
            'id' => (string) $content->id,
            'organization_id' => (string) $content->organizationId,
            'campaign_id' => (string) $content->campaignId,
            'created_by' => (string) $content->createdBy,
            'title' => $content->title,
            'body' => $content->body,
            'hashtags' => $content->hashtags,
            'status' => $content->status->value,
            'ai_generation_id' => $content->aiGenerationId,
            'deleted_at' => $content->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => $content->purgeAt?->format('Y-m-d H:i:s'),
        ];
    }
}
