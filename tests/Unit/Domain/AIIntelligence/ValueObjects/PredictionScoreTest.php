<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidPredictionScoreException;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;

it('creates with valid value', function () {
    $score = PredictionScore::create(75);

    expect($score->value)->toBe(75);
});

it('creates with boundary values 0 and 100', function () {
    $zero = PredictionScore::create(0);
    $hundred = PredictionScore::create(100);

    expect($zero->value)->toBe(0)
        ->and($hundred->value)->toBe(100);
});

it('throws on value < 0', function () {
    PredictionScore::create(-1);
})->throws(InvalidPredictionScoreException::class);

it('throws on value > 100', function () {
    PredictionScore::create(101);
})->throws(InvalidPredictionScoreException::class);

it('converts to string', function () {
    $score = PredictionScore::create(85);

    expect((string) $score)->toBe('85');
});
