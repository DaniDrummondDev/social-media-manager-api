<?php

declare(strict_types=1);

use App\Domain\PaidAdvertising\Entities\AdMetricSnapshot;
use App\Domain\PaidAdvertising\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;

it('auto-calculates CTR from clicks and impressions', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 1000,
        reach: 800,
        clicks: 50,
        spendCents: 500,
        spendCurrency: 'USD',
        conversions: 5,
    );

    expect($snapshot->ctr)->toBe(5.0); // (50/1000)*100
});

it('auto-calculates CPC from spend and clicks', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 1000,
        reach: 800,
        clicks: 50,
        spendCents: 500,
        spendCurrency: 'USD',
        conversions: 5,
    );

    expect($snapshot->cpc)->toBe(0.1); // 500/(100*50)
});

it('auto-calculates CPM from spend and impressions', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 1000,
        reach: 800,
        clicks: 50,
        spendCents: 500,
        spendCurrency: 'USD',
        conversions: 5,
    );

    expect($snapshot->cpm)->toBe(5.0); // (500/(100*1000))*1000
});

it('auto-calculates cost per conversion', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 1000,
        reach: 800,
        clicks: 50,
        spendCents: 500,
        spendCurrency: 'USD',
        conversions: 5,
    );

    expect($snapshot->costPerConversion)->toBe(1.0); // 500/(100*5)
});

it('handles zero impressions gracefully', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 0,
        reach: 0,
        clicks: 0,
        spendCents: 0,
        spendCurrency: 'USD',
        conversions: 0,
    );

    expect($snapshot->ctr)->toBe(0.0)
        ->and($snapshot->cpc)->toBeNull()
        ->and($snapshot->cpm)->toBeNull()
        ->and($snapshot->costPerConversion)->toBeNull();
});

it('handles zero clicks with non-zero impressions', function () {
    $snapshot = AdMetricSnapshot::create(
        boostId: Uuid::generate(),
        period: MetricPeriod::Daily,
        impressions: 1000,
        reach: 800,
        clicks: 0,
        spendCents: 500,
        spendCurrency: 'USD',
        conversions: 0,
    );

    expect($snapshot->ctr)->toBe(0.0)
        ->and($snapshot->cpc)->toBeNull()
        ->and($snapshot->cpm)->toBe(5.0);
});

it('reconstitutes with pre-calculated values', function () {
    $boostId = Uuid::generate();
    $capturedAt = new DateTimeImmutable;

    $snapshot = AdMetricSnapshot::reconstitute(
        id: Uuid::generate(),
        boostId: $boostId,
        period: MetricPeriod::Weekly,
        impressions: 5000,
        reach: 4000,
        clicks: 250,
        spendCents: 2500,
        spendCurrency: 'BRL',
        conversions: 25,
        ctr: 5.0,
        cpc: 0.1,
        cpm: 5.0,
        costPerConversion: 1.0,
        capturedAt: $capturedAt,
    );

    expect($snapshot->period)->toBe(MetricPeriod::Weekly)
        ->and($snapshot->impressions)->toBe(5000)
        ->and($snapshot->ctr)->toBe(5.0)
        ->and($snapshot->cpc)->toBe(0.1)
        ->and($snapshot->capturedAt)->toBe($capturedAt);
});
