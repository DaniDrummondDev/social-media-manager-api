<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialListening\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\Mention;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\Sentiment;
use App\Infrastructure\SocialListening\Models\MentionModel;
use DateTimeImmutable;

final class EloquentMentionRepository implements MentionRepositoryInterface
{
    public function __construct(
        private readonly MentionModel $model,
    ) {}

    public function create(Mention $mention): void
    {
        $this->model->newQuery()->create($this->toArray($mention));
    }

    /**
     * @param  array<Mention>  $mentions
     */
    public function createBatch(array $mentions): void
    {
        $records = array_map(fn (Mention $m) => $this->toArray($m), $mentions);

        $this->model->newQuery()->insert($records);
    }

    public function findById(Uuid $id): ?Mention
    {
        /** @var MentionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{items: array<Mention>, next_cursor: ?string}
     */
    public function findByOrganizationId(Uuid $organizationId, array $filters = [], ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId);

        if (isset($filters['query_id'])) {
            $query->where('query_id', $filters['query_id']);
        }

        if (isset($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (isset($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }

        if (isset($filters['is_flagged'])) {
            $query->where('is_flagged', $filters['is_flagged']);
        }

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }

        if (isset($filters['from'])) {
            $query->where('detected_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->where('detected_at', '<=', $filters['to']);
        }

        if (isset($filters['search'])) {
            $query->where('content', 'LIKE', '%' . $filters['search'] . '%');
        }

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit + 1);

        /** @var \Illuminate\Database\Eloquent\Collection<int, MentionModel> $records */
        $records = $query->get();

        $hasMore = $records->count() > $limit;
        $items = $hasMore ? $records->slice(0, $limit) : $records;

        $mapped = $items->map(fn (MentionModel $r) => $this->toDomain($r))->values()->all();

        return [
            'items' => $mapped,
            'next_cursor' => $hasMore ? (string) $items->last()?->getAttribute('id') : null,
        ];
    }

    /**
     * @return array<Mention>
     */
    public function findByQueryId(Uuid $queryId, ?string $cursor = null, int $limit = 20): array
    {
        $query = $this->model->newQuery()
            ->where('query_id', (string) $queryId);

        if ($cursor !== null) {
            $query->where('id', '<', $cursor);
        }

        $query->orderByDesc('id')->limit($limit);

        /** @var \Illuminate\Database\Eloquent\Collection<int, MentionModel> $records */
        $records = $query->get();

        return $records->map(fn (MentionModel $r) => $this->toDomain($r))->all();
    }

    public function countByQueryInPeriod(Uuid $queryId, DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return (int) $this->model->newQuery()
            ->where('query_id', (string) $queryId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->count();
    }

    public function existsByExternalId(string $externalId, string $platform): bool
    {
        return $this->model->newQuery()
            ->where('external_id', $externalId)
            ->where('platform', $platform)
            ->exists();
    }

    public function update(Mention $mention): void
    {
        $this->model->newQuery()
            ->where('id', (string) $mention->id)
            ->update($this->toArray($mention));
    }

    public function markAsRead(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->update(['is_read' => true]);
    }

    /**
     * @param  array<string>  $ids
     */
    public function markManyAsRead(Uuid $organizationId, array $ids): void
    {
        $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereIn('id', $ids)
            ->update(['is_read' => true]);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getSentimentTrend(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array
    {
        $query = $this->model->newQuery()
            ->selectRaw("DATE(detected_at) as date, sentiment, COUNT(*) as count")
            ->where('organization_id', (string) $organizationId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

        if ($queryId !== null) {
            $query->where('query_id', $queryId);
        }

        $query->groupByRaw('DATE(detected_at), sentiment')
            ->orderByRaw('DATE(detected_at)');

        $rows = $query->get();

        $grouped = [];

        foreach ($rows as $row) {
            $date = $row->getAttribute('date');
            $sentiment = $row->getAttribute('sentiment');
            $count = (int) $row->getAttribute('count');

            if (! isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                    'total' => 0,
                ];
            }

            if ($sentiment !== null) {
                $grouped[$date][$sentiment] = $count;
            }

            $grouped[$date]['total'] += $count;
        }

        return array_values($grouped);
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getPlatformBreakdown(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array
    {
        $query = $this->model->newQuery()
            ->selectRaw('platform, COUNT(*) as count')
            ->where('organization_id', (string) $organizationId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

        if ($queryId !== null) {
            $query->where('query_id', $queryId);
        }

        $query->groupBy('platform');

        $rows = $query->get();

        return $rows->map(fn ($row) => [
            'platform' => $row->getAttribute('platform'),
            'count' => (int) $row->getAttribute('count'),
        ])->all();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getTopAuthors(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null, int $limit = 10): array
    {
        $query = $this->model->newQuery()
            ->selectRaw('author_username, COUNT(*) as count')
            ->where('organization_id', (string) $organizationId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

        if ($queryId !== null) {
            $query->where('query_id', $queryId);
        }

        $query->groupBy('author_username')
            ->orderByDesc('count')
            ->limit($limit);

        $rows = $query->get();

        return $rows->map(fn ($row) => [
            'author_username' => $row->getAttribute('author_username'),
            'count' => (int) $row->getAttribute('count'),
        ])->all();
    }

    public function countByOrganizationInPeriod(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): int
    {
        $query = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

        if ($queryId !== null) {
            $query->where('query_id', $queryId);
        }

        return (int) $query->count();
    }

    /**
     * @return array<string, int>
     */
    public function getSentimentCounts(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to, ?string $queryId = null): array
    {
        $query = $this->model->newQuery()
            ->selectRaw('sentiment, COUNT(*) as count')
            ->where('organization_id', (string) $organizationId)
            ->whereBetween('detected_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);

        if ($queryId !== null) {
            $query->where('query_id', $queryId);
        }

        $query->groupBy('sentiment');

        $rows = $query->get();

        $result = [];

        foreach ($rows as $row) {
            $sentiment = $row->getAttribute('sentiment');

            if ($sentiment !== null) {
                $result[$sentiment] = (int) $row->getAttribute('count');
            }
        }

        return $result;
    }

    public function deleteOlderThan(DateTimeImmutable $before): int
    {
        return (int) $this->model->newQuery()
            ->where('detected_at', '<', $before->format('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Mention $mention): array
    {
        return [
            'id' => (string) $mention->id,
            'query_id' => (string) $mention->queryId,
            'organization_id' => (string) $mention->organizationId,
            'platform' => $mention->platform,
            'external_id' => $mention->externalId,
            'author_username' => $mention->authorUsername,
            'author_display_name' => $mention->authorDisplayName,
            'author_follower_count' => $mention->authorFollowerCount,
            'profile_url' => $mention->profileUrl,
            'content' => $mention->content,
            'url' => $mention->url,
            'sentiment' => $mention->sentiment?->value,
            'sentiment_score' => $mention->sentimentScore,
            'reach' => $mention->reach,
            'engagement_count' => $mention->engagementCount,
            'is_flagged' => $mention->isFlagged,
            'is_read' => $mention->isRead,
            'published_at' => $mention->publishedAt->format('Y-m-d H:i:s'),
            'detected_at' => $mention->detectedAt->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(MentionModel $model): Mention
    {
        $publishedAt = $model->getAttribute('published_at');
        $detectedAt = $model->getAttribute('detected_at');
        $sentiment = $model->getAttribute('sentiment');

        return Mention::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            queryId: Uuid::fromString($model->getAttribute('query_id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            platform: $model->getAttribute('platform'),
            externalId: $model->getAttribute('external_id'),
            authorUsername: $model->getAttribute('author_username'),
            authorDisplayName: $model->getAttribute('author_display_name'),
            authorFollowerCount: $model->getAttribute('author_follower_count') !== null ? (int) $model->getAttribute('author_follower_count') : null,
            profileUrl: $model->getAttribute('profile_url'),
            content: $model->getAttribute('content'),
            url: $model->getAttribute('url'),
            sentiment: $sentiment !== null ? Sentiment::from($sentiment) : null,
            sentimentScore: $model->getAttribute('sentiment_score') !== null ? (float) $model->getAttribute('sentiment_score') : null,
            reach: (int) $model->getAttribute('reach'),
            engagementCount: (int) $model->getAttribute('engagement_count'),
            isFlagged: (bool) $model->getAttribute('is_flagged'),
            isRead: (bool) $model->getAttribute('is_read'),
            publishedAt: new DateTimeImmutable($publishedAt->format('Y-m-d H:i:s')),
            detectedAt: new DateTimeImmutable($detectedAt->format('Y-m-d H:i:s')),
        );
    }
}
