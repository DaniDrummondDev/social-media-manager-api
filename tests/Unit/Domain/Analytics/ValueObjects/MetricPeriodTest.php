<?php

declare(strict_types=1);

use App\Domain\Analytics\Exceptions\InvalidMetricPeriodException;
use App\Domain\Analytics\ValueObjects\MetricPeriod;

it('creates from 7d preset', function () {
    $period = MetricPeriod::fromPreset('7d');

    expect($period->type)->toBe('7d')
        ->and($period->from)->toBeLessThan($period->to)
        ->and($period->days())->toBeGreaterThanOrEqual(7);
});

it('creates from 30d preset', function () {
    $period = MetricPeriod::fromPreset('30d');

    expect($period->type)->toBe('30d')
        ->and($period->days())->toBeGreaterThanOrEqual(30);
});

it('creates from 90d preset', function () {
    $period = MetricPeriod::fromPreset('90d');

    expect($period->type)->toBe('90d')
        ->and($period->days())->toBeGreaterThanOrEqual(90);
});

it('throws on unknown preset', function () {
    MetricPeriod::fromPreset('invalid');
})->throws(InvalidMetricPeriodException::class);

it('creates custom period', function () {
    $from = new DateTimeImmutable('2026-01-01');
    $to = new DateTimeImmutable('2026-01-31');
    $period = MetricPeriod::custom($from, $to);

    expect($period->type)->toBe('custom')
        ->and($period->from)->toEqual($from)
        ->and($period->to)->toEqual($to)
        ->and($period->days())->toBe(31);
});

it('throws when from is after to', function () {
    MetricPeriod::custom(
        new DateTimeImmutable('2026-02-01'),
        new DateTimeImmutable('2026-01-01'),
    );
})->throws(InvalidMetricPeriodException::class);

it('calculates previous period', function () {
    $from = new DateTimeImmutable('2026-01-15 00:00:00');
    $to = new DateTimeImmutable('2026-01-21 23:59:59');
    $period = MetricPeriod::custom($from, $to);
    $previous = $period->previousPeriod();

    expect($previous->type)->toBe('comparison')
        ->and($previous->to)->toBeLessThan($period->from);
});
