<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\Repositories\AdMetricSnapshotRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PaidAdvertising\Models\AdMetricSnapshotModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class EloquentAdMetricSnapshotRepository implements AdMetricSnapshotRepositoryInterface
{
    public function __construct(
        private readonly AdMetricSnapshotModel $model,
    ) {}

    public function create(AdMetricSnapshot $snapshot): void
    {
        $this->model->newQuery()->create($this->toArray($snapshot));
    }

    /**
     * @param  array<AdMetricSnapshot>  $snapshots
     */
    public function createBatch(array $snapshots): void
    {
        if ($snapshots === []) {
            return;
        }

        $this->model->newQuery()->insert(
            array_map(fn (AdMetricSnapshot $s) => $this->toArray($s), $snapshots),
        );
    }

    /**
     * @return array<AdMetricSnapshot>
     */
    public function findByBoostId(Uuid $boostId): array
    {
        $records = $this->model->newQuery()
            ->where('boost_id', (string) $boostId)
            ->orderByDesc('captured_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<AdMetricSnapshot>
     */
    public function findByBoostIdAndPeriod(Uuid $boostId, MetricPeriod $period): array
    {
        $records = $this->model->newQuery()
            ->where('boost_id', (string) $boostId)
            ->where('period', $period->value)
            ->orderByDesc('captured_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array{total_spend_cents: int, currency: string}
     */
    public function getTotalSpend(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $result = DB::table('ad_metric_snapshots')
            ->join('ad_boosts', 'ad_metric_snapshots.boost_id', '=', 'ad_boosts.id')
            ->where('ad_boosts.organization_id', (string) $organizationId)
            ->whereBetween('ad_metric_snapshots.captured_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ])
            ->selectRaw('COALESCE(SUM(ad_metric_snapshots.spend_cents), 0) as total_spend_cents')
            ->selectRaw('COALESCE(ad_metric_snapshots.spend_currency, \'USD\') as currency')
            ->groupBy('ad_metric_snapshots.spend_currency')
            ->first();

        return [
            'total_spend_cents' => (int) ($result?->total_spend_cents ?? 0),
            'currency' => $result?->currency ?? 'USD',
        ];
    }

    /**
     * @return array<array{date: string, spend_cents: int, impressions: int, clicks: int, conversions: int}>
     */
    public function getSpendingHistory(Uuid $organizationId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $results = DB::table('ad_metric_snapshots')
            ->join('ad_boosts', 'ad_metric_snapshots.boost_id', '=', 'ad_boosts.id')
            ->where('ad_boosts.organization_id', (string) $organizationId)
            ->whereBetween('ad_metric_snapshots.captured_at', [
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ])
            ->selectRaw('DATE(ad_metric_snapshots.captured_at) as date')
            ->selectRaw('COALESCE(SUM(ad_metric_snapshots.spend_cents), 0) as spend_cents')
            ->selectRaw('COALESCE(SUM(ad_metric_snapshots.impressions), 0) as impressions')
            ->selectRaw('COALESCE(SUM(ad_metric_snapshots.clicks), 0) as clicks')
            ->selectRaw('COALESCE(SUM(ad_metric_snapshots.conversions), 0) as conversions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $results->map(fn (object $row) => [
            'date' => $row->date,
            'spend_cents' => (int) $row->spend_cents,
            'impressions' => (int) $row->impressions,
            'clicks' => (int) $row->clicks,
            'conversions' => (int) $row->conversions,
        ])->all();
    }

    public function getLatestByBoostId(Uuid $boostId): ?AdMetricSnapshot
    {
        $record = $this->model->newQuery()
            ->where('boost_id', (string) $boostId)
            ->orderByDesc('captured_at')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    private function toDomain(Model $model): AdMetricSnapshot
    {
        $capturedAt = $model->getAttribute('captured_at');

        return AdMetricSnapshot::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            boostId: Uuid::fromString($model->getAttribute('boost_id')),
            period: MetricPeriod::from($model->getAttribute('period')),
            impressions: (int) $model->getAttribute('impressions'),
            reach: (int) $model->getAttribute('reach'),
            clicks: (int) $model->getAttribute('clicks'),
            spendCents: (int) $model->getAttribute('spend_cents'),
            spendCurrency: $model->getAttribute('spend_currency'),
            conversions: (int) $model->getAttribute('conversions'),
            ctr: (float) $model->getAttribute('ctr'),
            cpc: $model->getAttribute('cpc') !== null ? (float) $model->getAttribute('cpc') : null,
            cpm: $model->getAttribute('cpm') !== null ? (float) $model->getAttribute('cpm') : null,
            costPerConversion: $model->getAttribute('cost_per_conversion') !== null ? (float) $model->getAttribute('cost_per_conversion') : null,
            capturedAt: $capturedAt instanceof \DateTimeInterface
                ? new DateTimeImmutable($capturedAt->format('Y-m-d H:i:s'))
                : new DateTimeImmutable($capturedAt),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AdMetricSnapshot $snapshot): array
    {
        return [
            'id' => (string) $snapshot->id,
            'boost_id' => (string) $snapshot->boostId,
            'period' => $snapshot->period->value,
            'impressions' => $snapshot->impressions,
            'reach' => $snapshot->reach,
            'clicks' => $snapshot->clicks,
            'spend_cents' => $snapshot->spendCents,
            'spend_currency' => $snapshot->spendCurrency,
            'conversions' => $snapshot->conversions,
            'ctr' => $snapshot->ctr,
            'cpc' => $snapshot->cpc,
            'cpm' => $snapshot->cpm,
            'cost_per_conversion' => $snapshot->costPerConversion,
            'captured_at' => $snapshot->capturedAt->format('Y-m-d H:i:s'),
        ];
    }
}
