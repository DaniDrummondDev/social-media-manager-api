<?php

declare(strict_types=1);

use App\Domain\ContentAI\ValueObjects\PerformanceScore;

it('calculates performance score with formula', function () {
    // Formula: (accepted + edited × 0.7) / total_uses × 100
    // (8 + 2 × 0.7) / 10 × 100 = 94.0
    $score = PerformanceScore::calculate(10, 8, 2);

    expect($score->value)->toBe(94.0);
});

it('returns zero for zero uses', function () {
    $score = PerformanceScore::calculate(0, 0, 0);

    expect($score->value)->toBe(0.0);
});

it('caps at 100', function () {
    // All accepted: (10 + 0 × 0.7) / 10 × 100 = 100.0
    $score = PerformanceScore::calculate(10, 10, 0);

    expect($score->value)->toBe(100.0);
});

it('handles all rejected (zero accepted and edited)', function () {
    // (0 + 0 × 0.7) / 10 × 100 = 0.0
    $score = PerformanceScore::calculate(10, 0, 0);

    expect($score->value)->toBe(0.0);
});

it('creates from float', function () {
    $score = PerformanceScore::fromFloat(85.5);

    expect($score->value)->toBe(85.5);
});

it('throws on fromFloat below 0', function () {
    PerformanceScore::fromFloat(-1.0);
})->throws(DomainException::class);

it('throws on fromFloat above 100', function () {
    PerformanceScore::fromFloat(100.1);
})->throws(DomainException::class);

it('isEligibleForAutoSelection returns true when score above 0', function () {
    $score = PerformanceScore::fromFloat(50.0);

    expect($score->isEligibleForAutoSelection())->toBeTrue();
});

it('isEligibleForAutoSelection returns false when score is zero', function () {
    $score = PerformanceScore::fromFloat(0.0);

    expect($score->isEligibleForAutoSelection())->toBeFalse();
});

it('converts to string', function () {
    $score = PerformanceScore::fromFloat(75.5);

    expect((string) $score)->toBe('75.5');
});
