<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Repositories;

use App\Domain\Analytics\Entities\AccountMetric;
use App\Domain\Analytics\Repositories\AccountMetricRepositoryInterface;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\Analytics\Models\AccountMetricModel;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentAccountMetricRepository implements AccountMetricRepositoryInterface
{
    public function __construct(
        private readonly AccountMetricModel $model,
    ) {}

    public function upsert(AccountMetric $metric): void
    {
        $this->model->newQuery()->updateOrCreate(
            [
                'social_account_id' => (string) $metric->socialAccountId,
                'date' => $metric->date->format('Y-m-d'),
            ],
            $this->toArray($metric),
        );
    }

    public function findBySocialAccountId(Uuid $socialAccountId): ?AccountMetric
    {
        /** @var AccountMetricModel|null $record */
        $record = $this->model->newQuery()
            ->where('social_account_id', (string) $socialAccountId)
            ->orderByDesc('date')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function getLatest(Uuid $socialAccountId): ?AccountMetric
    {
        return $this->findBySocialAccountId($socialAccountId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFollowersTrend(Uuid $socialAccountId, MetricPeriod $period): array
    {
        $results = DB::table('account_metrics')
            ->where('social_account_id', (string) $socialAccountId)
            ->where('date', '>=', $period->from->format('Y-m-d'))
            ->where('date', '<=', $period->to->format('Y-m-d'))
            ->select(['date', 'followers_count', 'followers_gained', 'followers_lost'])
            ->orderBy('date')
            ->get();

        return $results->map(fn ($r) => (array) $r)->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountSummary(Uuid $socialAccountId, MetricPeriod $period): array
    {
        $result = DB::table('account_metrics')
            ->where('social_account_id', (string) $socialAccountId)
            ->where('date', '>=', $period->from->format('Y-m-d'))
            ->where('date', '<=', $period->to->format('Y-m-d'))
            ->selectRaw('
                COALESCE(SUM(followers_gained), 0) as total_followers_gained,
                COALESCE(SUM(followers_lost), 0) as total_followers_lost,
                COALESCE(SUM(profile_views), 0) as total_profile_views,
                COALESCE(SUM(reach), 0) as total_reach,
                COALESCE(SUM(impressions), 0) as total_impressions
            ')
            ->first();

        /** @var AccountMetricModel|null $latest */
        $latest = $this->model->newQuery()
            ->where('social_account_id', (string) $socialAccountId)
            ->orderByDesc('date')
            ->first();

        $summaryArray = (array) $result;
        $summaryArray['followers_count'] = $latest ? (int) $latest->getAttribute('followers_count') : 0;

        return $summaryArray;
    }

    private function toDomain(AccountMetricModel $model): AccountMetric
    {
        $date = $model->getAttribute('date');
        $syncedAt = $model->getAttribute('synced_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return AccountMetric::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            socialAccountId: Uuid::fromString($model->getAttribute('social_account_id')),
            provider: SocialProvider::from($model->getAttribute('provider')),
            date: new DateTimeImmutable($date->format('Y-m-d')),
            followersCount: (int) $model->getAttribute('followers_count'),
            followersGained: (int) $model->getAttribute('followers_gained'),
            followersLost: (int) $model->getAttribute('followers_lost'),
            profileViews: (int) $model->getAttribute('profile_views'),
            reach: (int) $model->getAttribute('reach'),
            impressions: (int) $model->getAttribute('impressions'),
            syncedAt: new DateTimeImmutable($syncedAt->toDateTimeString()),
            createdAt: new DateTimeImmutable($createdAt->toDateTimeString()),
            updatedAt: new DateTimeImmutable($updatedAt->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AccountMetric $metric): array
    {
        return [
            'id' => (string) $metric->id,
            'social_account_id' => (string) $metric->socialAccountId,
            'provider' => $metric->provider->value,
            'date' => $metric->date->format('Y-m-d'),
            'followers_count' => $metric->followersCount,
            'followers_gained' => $metric->followersGained,
            'followers_lost' => $metric->followersLost,
            'profile_views' => $metric->profileViews,
            'reach' => $metric->reach,
            'impressions' => $metric->impressions,
            'synced_at' => $metric->syncedAt->format('Y-m-d H:i:s'),
        ];
    }
}
