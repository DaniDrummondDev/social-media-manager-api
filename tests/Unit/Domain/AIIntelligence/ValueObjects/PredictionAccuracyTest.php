<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy;

it('calculates accuracy from predicted and actual scores', function () {
    // accuracy = 100 - |80 - 75| = 95
    $accuracy = PredictionAccuracy::calculate(80, 75);

    expect($accuracy->absoluteError)->toBe(5)
        ->and($accuracy->accuracyPercentage)->toBe(95.0);
});

it('calculates 100% accuracy for exact match', function () {
    $accuracy = PredictionAccuracy::calculate(50, 50);

    expect($accuracy->absoluteError)->toBe(0)
        ->and($accuracy->accuracyPercentage)->toBe(100.0);
});

it('calculates with large error', function () {
    $accuracy = PredictionAccuracy::calculate(100, 0);

    expect($accuracy->absoluteError)->toBe(100)
        ->and($accuracy->accuracyPercentage)->toBe(0.0);
});

it('creates from values', function () {
    $accuracy = PredictionAccuracy::fromValues(10, 90.0);

    expect($accuracy->absoluteError)->toBe(10)
        ->and($accuracy->accuracyPercentage)->toBe(90.0);
});

it('returns grade A for 90+', function () {
    $accuracy = PredictionAccuracy::fromValues(5, 95.0);

    expect($accuracy->grade())->toBe('A');
});

it('returns grade B for 75-89', function () {
    $accuracy = PredictionAccuracy::fromValues(20, 80.0);

    expect($accuracy->grade())->toBe('B');
});

it('returns grade C for 60-74', function () {
    $accuracy = PredictionAccuracy::fromValues(35, 65.0);

    expect($accuracy->grade())->toBe('C');
});

it('returns grade D for 40-59', function () {
    $accuracy = PredictionAccuracy::fromValues(55, 45.0);

    expect($accuracy->grade())->toBe('D');
});

it('returns grade F for below 40', function () {
    $accuracy = PredictionAccuracy::fromValues(70, 30.0);

    expect($accuracy->grade())->toBe('F');
});

it('isGoodPrediction returns true for 75+', function () {
    $good = PredictionAccuracy::fromValues(10, 90.0);
    $borderline = PredictionAccuracy::fromValues(25, 75.0);
    $bad = PredictionAccuracy::fromValues(30, 70.0);

    expect($good->isGoodPrediction())->toBeTrue()
        ->and($borderline->isGoodPrediction())->toBeTrue()
        ->and($bad->isGoodPrediction())->toBeFalse();
});
