<?php

declare(strict_types=1);

namespace App\Infrastructure\Campaign\Repositories;

use App\Domain\Campaign\Contracts\ContentNetworkOverrideRepositoryInterface;
use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Campaign\Models\ContentNetworkOverrideModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentContentNetworkOverrideRepository implements ContentNetworkOverrideRepositoryInterface
{
    public function __construct(
        private readonly ContentNetworkOverrideModel $model,
    ) {}

    /**
     * @param  ContentNetworkOverride[]  $overrides
     */
    public function createMany(array $overrides): void
    {
        if ($overrides === []) {
            return;
        }

        $rows = array_map(fn (ContentNetworkOverride $override) => $this->toArray($override), $overrides);

        $this->model->newQuery()->insert($rows);
    }

    /**
     * @return ContentNetworkOverride[]
     */
    public function findByContentId(Uuid $contentId): array
    {
        $records = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->orderBy('provider')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ContentNetworkOverrideModel> $records */
        return $records->map(fn (ContentNetworkOverrideModel $record) => $this->toDomain($record))->all();
    }

    public function deleteByContentId(Uuid $contentId): void
    {
        $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->delete();
    }

    /**
     * @param  ContentNetworkOverride[]  $overrides
     */
    public function replaceForContent(Uuid $contentId, array $overrides): void
    {
        DB::transaction(function () use ($contentId, $overrides): void {
            $this->deleteByContentId($contentId);
            $this->createMany($overrides);
        });
    }

    private function toDomain(ContentNetworkOverrideModel $model): ContentNetworkOverride
    {
        return ContentNetworkOverride::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            provider: SocialProvider::from($model->getAttribute('provider')),
            title: $model->getAttribute('title'),
            body: $model->getAttribute('body'),
            hashtags: $model->getAttribute('hashtags'),
            metadata: $model->getAttribute('metadata'),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ContentNetworkOverride $override): array
    {
        return [
            'id' => (string) $override->id,
            'content_id' => (string) $override->contentId,
            'provider' => $override->provider->value,
            'title' => $override->title,
            'body' => $override->body,
            'hashtags' => $override->hashtags !== null ? json_encode($override->hashtags) : null,
            'metadata' => $override->metadata !== null ? json_encode($override->metadata) : null,
            'created_at' => $override->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $override->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
