<?php

declare(strict_types=1);

use App\Domain\ClientFinance\Exceptions\InvalidYearMonthException;
use App\Domain\ClientFinance\ValueObjects\YearMonth;

it('creates from valid string format', function () {
    $ym = YearMonth::fromString('2026-01');

    expect($ym->year)->toBe(2026)
        ->and($ym->month)->toBe(1);
});

it('creates from string with leading zero month', function () {
    $ym = YearMonth::fromString('2025-09');

    expect($ym->year)->toBe(2025)
        ->and($ym->month)->toBe(9);
});

it('throws for invalid format without leading zero', function () {
    expect(fn () => YearMonth::fromString('2026-1'))
        ->toThrow(InvalidYearMonthException::class);
});

it('throws for invalid format with wrong separator', function () {
    expect(fn () => YearMonth::fromString('2026/01'))
        ->toThrow(InvalidYearMonthException::class);
});

it('throws for invalid month 00', function () {
    expect(fn () => YearMonth::fromString('2026-00'))
        ->toThrow(InvalidYearMonthException::class);
});

it('throws for invalid month 13', function () {
    expect(fn () => YearMonth::fromString('2026-13'))
        ->toThrow(InvalidYearMonthException::class);
});

it('throws for completely invalid string', function () {
    expect(fn () => YearMonth::fromString('abc'))
        ->toThrow(InvalidYearMonthException::class);
});

it('creates current year-month', function () {
    $ym = YearMonth::current();
    $now = new DateTimeImmutable;

    expect($ym->year)->toBe((int) $now->format('Y'))
        ->and($ym->month)->toBe((int) $now->format('m'));
});

it('converts to string correctly', function () {
    $ym = YearMonth::fromString('2026-02');

    expect($ym->toString())->toBe('2026-02');
});

it('converts to string via __toString', function () {
    $ym = YearMonth::fromString('2026-02');

    expect((string) $ym)->toBe('2026-02');
});

it('compares equal YearMonth instances', function () {
    $a = YearMonth::fromString('2026-02');
    $b = YearMonth::fromString('2026-02');

    expect($a->equals($b))->toBeTrue();
});

it('compares different YearMonth instances', function () {
    $a = YearMonth::fromString('2026-02');
    $b = YearMonth::fromString('2026-03');

    expect($a->equals($b))->toBeFalse();
});

it('returns start of month', function () {
    $ym = YearMonth::fromString('2026-02');
    $start = $ym->startOfMonth();

    expect($start->format('Y-m-d H:i:s'))->toBe('2026-02-01 00:00:00');
});

it('returns end of month', function () {
    $ym = YearMonth::fromString('2026-02');
    $end = $ym->endOfMonth();

    expect($end->format('Y-m-d'))->toBe('2026-02-28')
        ->and($end->format('H:i:s'))->toBe('23:59:59');
});

it('returns end of month for leap year february', function () {
    $ym = YearMonth::fromString('2024-02');
    $end = $ym->endOfMonth();

    expect($end->format('Y-m-d'))->toBe('2024-02-29');
});
