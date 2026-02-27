<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Entities;

use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class AdMetricSnapshot
{
    public function __construct(
        public Uuid $id,
        public Uuid $boostId,
        public MetricPeriod $period,
        public int $impressions,
        public int $reach,
        public int $clicks,
        public int $spendCents,
        public string $spendCurrency,
        public int $conversions,
        public float $ctr,
        public ?float $cpc,
        public ?float $cpm,
        public ?float $costPerConversion,
        public DateTimeImmutable $capturedAt,
    ) {}

    public static function create(
        Uuid $boostId,
        MetricPeriod $period,
        int $impressions,
        int $reach,
        int $clicks,
        int $spendCents,
        string $spendCurrency,
        int $conversions,
    ): self {
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0.0;
        $cpc = $clicks > 0 ? $spendCents / (100 * $clicks) : null;
        $cpm = $impressions > 0 ? ($spendCents / (100 * $impressions)) * 1000 : null;
        $costPerConversion = $conversions > 0 ? $spendCents / (100 * $conversions) : null;

        return new self(
            id: Uuid::generate(),
            boostId: $boostId,
            period: $period,
            impressions: $impressions,
            reach: $reach,
            clicks: $clicks,
            spendCents: $spendCents,
            spendCurrency: $spendCurrency,
            conversions: $conversions,
            ctr: $ctr,
            cpc: $cpc,
            cpm: $cpm,
            costPerConversion: $costPerConversion,
            capturedAt: new DateTimeImmutable,
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $boostId,
        MetricPeriod $period,
        int $impressions,
        int $reach,
        int $clicks,
        int $spendCents,
        string $spendCurrency,
        int $conversions,
        float $ctr,
        ?float $cpc,
        ?float $cpm,
        ?float $costPerConversion,
        DateTimeImmutable $capturedAt,
    ): self {
        return new self(
            id: $id,
            boostId: $boostId,
            period: $period,
            impressions: $impressions,
            reach: $reach,
            clicks: $clicks,
            spendCents: $spendCents,
            spendCurrency: $spendCurrency,
            conversions: $conversions,
            ctr: $ctr,
            cpc: $cpc,
            cpm: $cpm,
            costPerConversion: $costPerConversion,
            capturedAt: $capturedAt,
        );
    }
}
