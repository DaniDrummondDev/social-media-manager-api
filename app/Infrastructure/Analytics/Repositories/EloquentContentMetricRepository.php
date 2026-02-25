<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Repositories;

use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Analytics\Models\ContentMetricModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentContentMetricRepository implements ContentMetricRepositoryInterface
{
    public function __construct(
        private readonly ContentMetricModel $model,
    ) {}

    public function upsert(ContentMetric $metric): void
    {
        $this->model->newQuery()->updateOrCreate(
            [
                'content_id' => (string) $metric->contentId,
                'social_account_id' => (string) $metric->socialAccountId,
            ],
            $this->toArray($metric),
        );
    }

    public function findByContentAndAccount(Uuid $contentId, Uuid $socialAccountId): ?ContentMetric
    {
        /** @var ContentMetricModel|null $record */
        $record = $this->model->newQuery()
            ->where('content_id', (string) $contentId)
            ->where('social_account_id', (string) $socialAccountId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<ContentMetric>
     */
    public function findTopByEngagement(Uuid $organizationId, MetricPeriod $period, int $limit = 5): array
    {
        $records = $this->model->newQuery()
            ->join('contents', 'content_metrics.content_id', '=', 'contents.id')
            ->where('contents.organization_id', (string) $organizationId)
            ->where('content_metrics.synced_at', '>=', $period->from->format('Y-m-d H:i:s'))
            ->where('content_metrics.synced_at', '<=', $period->to->format('Y-m-d H:i:s'))
            ->orderByDesc('content_metrics.engagement_rate')
            ->limit($limit)
            ->select('content_metrics.*')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, ContentMetricModel> $records */
        return $records->map(fn (ContentMetricModel $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAggregatedMetrics(Uuid $organizationId, MetricPeriod $period): array
    {
        $result = DB::table('content_metrics')
            ->join('contents', 'content_metrics.content_id', '=', 'contents.id')
            ->where('contents.organization_id', (string) $organizationId)
            ->where('content_metrics.synced_at', '>=', $period->from->format('Y-m-d H:i:s'))
            ->where('content_metrics.synced_at', '<=', $period->to->format('Y-m-d H:i:s'))
            ->selectRaw('
                COALESCE(SUM(content_metrics.impressions), 0) as impressions,
                COALESCE(SUM(content_metrics.reach), 0) as reach,
                COALESCE(SUM(content_metrics.likes), 0) as likes,
                COALESCE(SUM(content_metrics.comments), 0) as comments,
                COALESCE(SUM(content_metrics.shares), 0) as shares,
                COALESCE(SUM(content_metrics.saves), 0) as saves,
                COALESCE(SUM(content_metrics.clicks), 0) as clicks,
                COALESCE(AVG(content_metrics.engagement_rate), 0) as engagement_rate,
                COUNT(*) as total_posts
            ')
            ->first();

        return (array) $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDailyTrend(Uuid $organizationId, MetricPeriod $period): array
    {
        $driver = DB::getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? 'DATE(content_metrics.synced_at)'
            : 'DATE(content_metrics.synced_at)';

        $results = DB::table('content_metrics')
            ->join('contents', 'content_metrics.content_id', '=', 'contents.id')
            ->where('contents.organization_id', (string) $organizationId)
            ->where('content_metrics.synced_at', '>=', $period->from->format('Y-m-d H:i:s'))
            ->where('content_metrics.synced_at', '<=', $period->to->format('Y-m-d H:i:s'))
            ->selectRaw("
                {$dateExpr} as date,
                SUM(content_metrics.impressions) as impressions,
                SUM(content_metrics.reach) as reach,
                SUM(content_metrics.likes) as likes,
                SUM(content_metrics.comments) as comments,
                AVG(content_metrics.engagement_rate) as engagement_rate
            ")
            ->groupByRaw($dateExpr)
            ->orderBy('date')
            ->get();

        return $results->map(fn ($r) => (array) $r)->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getByNetworkSummary(Uuid $organizationId, MetricPeriod $period): array
    {
        $results = DB::table('content_metrics')
            ->join('contents', 'content_metrics.content_id', '=', 'contents.id')
            ->where('contents.organization_id', (string) $organizationId)
            ->where('content_metrics.synced_at', '>=', $period->from->format('Y-m-d H:i:s'))
            ->where('content_metrics.synced_at', '<=', $period->to->format('Y-m-d H:i:s'))
            ->selectRaw('
                content_metrics.provider,
                SUM(content_metrics.impressions) as impressions,
                SUM(content_metrics.reach) as reach,
                SUM(content_metrics.likes) as likes,
                SUM(content_metrics.comments) as comments,
                SUM(content_metrics.shares) as shares,
                SUM(content_metrics.saves) as saves,
                SUM(content_metrics.clicks) as clicks,
                AVG(content_metrics.engagement_rate) as engagement_rate,
                COUNT(*) as total_posts
            ')
            ->groupBy('content_metrics.provider')
            ->get();

        $summary = [];
        foreach ($results as $row) {
            $summary[$row->provider] = (array) $row;
        }

        return $summary;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBestPostingTimes(Uuid $organizationId, ?string $provider = null, int $minPosts = 3): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $dayExpr = "CAST(strftime('%w', scheduled_posts.published_at) AS INTEGER)";
            $hourExpr = "CAST(strftime('%H', scheduled_posts.published_at) AS INTEGER)";
        } else {
            $dayExpr = 'EXTRACT(DOW FROM scheduled_posts.published_at)::INTEGER';
            $hourExpr = 'EXTRACT(HOUR FROM scheduled_posts.published_at)::INTEGER';
        }

        $query = DB::table('content_metrics')
            ->join('contents', 'content_metrics.content_id', '=', 'contents.id')
            ->join('scheduled_posts', function ($join) {
                $join->on('scheduled_posts.content_id', '=', 'content_metrics.content_id')
                    ->on('scheduled_posts.social_account_id', '=', 'content_metrics.social_account_id');
            })
            ->where('contents.organization_id', (string) $organizationId)
            ->whereNotNull('scheduled_posts.published_at');

        if ($provider !== null) {
            $query->where('content_metrics.provider', $provider);
        }

        $results = $query->selectRaw("
                {$dayExpr} as day_of_week,
                {$hourExpr} as hour,
                AVG(content_metrics.engagement_rate) as avg_engagement_rate,
                COUNT(*) as post_count
            ")
            ->groupByRaw("{$dayExpr}, {$hourExpr}")
            ->havingRaw('COUNT(*) >= ?', [$minPosts])
            ->orderByDesc('avg_engagement_rate')
            ->limit(10)
            ->get();

        return $results->map(fn ($r) => (array) $r)->all();
    }

    private function toDomain(ContentMetricModel $model): ContentMetric
    {
        $syncedAt = $model->getAttribute('synced_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return ContentMetric::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            contentId: Uuid::fromString($model->getAttribute('content_id')),
            socialAccountId: Uuid::fromString($model->getAttribute('social_account_id')),
            provider: SocialProvider::from($model->getAttribute('provider')),
            externalPostId: $model->getAttribute('external_post_id'),
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
            syncedAt: new DateTimeImmutable($syncedAt->toDateTimeString()),
            createdAt: new DateTimeImmutable($createdAt->toDateTimeString()),
            updatedAt: new DateTimeImmutable($updatedAt->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(ContentMetric $metric): array
    {
        return [
            'id' => (string) $metric->id,
            'content_id' => (string) $metric->contentId,
            'social_account_id' => (string) $metric->socialAccountId,
            'provider' => $metric->provider->value,
            'external_post_id' => $metric->externalPostId,
            'impressions' => $metric->impressions,
            'reach' => $metric->reach,
            'likes' => $metric->likes,
            'comments' => $metric->comments,
            'shares' => $metric->shares,
            'saves' => $metric->saves,
            'clicks' => $metric->clicks,
            'views' => $metric->views,
            'watch_time_seconds' => $metric->watchTimeSeconds,
            'engagement_rate' => $metric->engagementRate,
            'synced_at' => $metric->syncedAt->format('Y-m-d H:i:s'),
        ];
    }
}
