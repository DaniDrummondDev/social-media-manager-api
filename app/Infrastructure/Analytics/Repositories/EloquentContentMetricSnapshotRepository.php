<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Repositories;

use App\Domain\Analytics\Entities\ContentMetricSnapshot;
use App\Domain\Analytics\Repositories\ContentMetricSnapshotRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Analytics\Models\ContentMetricSnapshotModel;
use DateTimeImmutable;

final class EloquentContentMetricSnapshotRepository implements ContentMetricSnapshotRepositoryInterface
{
    public function __construct(
        private readonly ContentMetricSnapshotModel $model,
    ) {}

    public function create(ContentMetricSnapshot $snapshot): void
    {
        $this->model->newQuery()->create([
            'id' => (string) $snapshot->id,
            'content_metric_id' => (string) $snapshot->contentMetricId,
            'impressions' => $snapshot->impressions,
            'reach' => $snapshot->reach,
            'likes' => $snapshot->likes,
            'comments' => $snapshot->comments,
            'shares' => $snapshot->shares,
            'saves' => $snapshot->saves,
            'clicks' => $snapshot->clicks,
            'views' => $snapshot->views,
            'watch_time_seconds' => $snapshot->watchTimeSeconds,
            'engagement_rate' => $snapshot->engagementRate,
            'captured_at' => $snapshot->capturedAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<ContentMetricSnapshot>
     */
    public function findByMetricsId(Uuid $contentMetricId): array
    {
        $records = $this->model->newQuery()
            ->where('content_metric_id', (string) $contentMetricId)
            ->orderBy('captured_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ContentMetricSnapshotModel> $records */
        return $records->map(fn (ContentMetricSnapshotModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEvolution(Uuid $contentMetricId, DateTimeImmutable $publishedAt): array
    {
        $milestones = [
            '24h' => $publishedAt->modify('+24 hours'),
            '48h' => $publishedAt->modify('+48 hours'),
            '7d' => $publishedAt->modify('+7 days'),
        ];

        $evolution = [];

        foreach ($milestones as $label => $targetTime) {
            /** @var ContentMetricSnapshotModel|null $snapshot */
            $snapshot = $this->model->newQuery()
                ->where('content_metric_id', (string) $contentMetricId)
                ->where('captured_at', '<=', $targetTime->format('Y-m-d H:i:s'))
                ->orderByDesc('captured_at')
                ->first();

            if ($snapshot !== null) {
                $evolution[$label] = [
                    'impressions' => (int) $snapshot->getAttribute('impressions'),
                    'reach' => (int) $snapshot->getAttribute('reach'),
                    'likes' => (int) $snapshot->getAttribute('likes'),
                    'comments' => (int) $snapshot->getAttribute('comments'),
                    'shares' => (int) $snapshot->getAttribute('shares'),
                    'saves' => (int) $snapshot->getAttribute('saves'),
                    'engagement_rate' => (float) $snapshot->getAttribute('engagement_rate'),
                    'captured_at' => $snapshot->getAttribute('captured_at')->toDateTimeString(),
                ];
            }
        }

        return $evolution;
    }

    private function toDomain(ContentMetricSnapshotModel $model): ContentMetricSnapshot
    {
        return ContentMetricSnapshot::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            contentMetricId: Uuid::fromString($model->getAttribute('content_metric_id')),
            impressions: (int) $model->getAttribute('impressions'),
            reach: (int) $model->getAttribute('reach'),
            likes: (int) $model->getAttribute('likes'),
            comments: (int) $model->getAttribute('comments'),
            shares: (int) $model->getAttribute('shares'),
            saves: (int) $model->getAttribute('saves'),
            clicks: (int) $model->getAttribute('clicks'),
            views: $model->getAttribute('views') !== null ? (int) $model->getAttribute('views') : null,
            watchTimeSeconds: $model->getAttribute('watch_time_seconds') !== null ? (int) $model->getAttribute('watch_time_seconds') : null,
            engagementRate: (float) $model->getAttribute('engagement_rate'),
            capturedAt: new DateTimeImmutable($model->getAttribute('captured_at')->toDateTimeString()),
        );
    }
}
