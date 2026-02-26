<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidPredictionBreakdownException;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;

it('creates a valid breakdown', function () {
    $breakdown = PredictionBreakdown::create(80, 70, 60, 75, 90);

    expect($breakdown->contentSimilarity)->toBe(80)
        ->and($breakdown->timing)->toBe(70)
        ->and($breakdown->hashtags)->toBe(60)
        ->and($breakdown->length)->toBe(75)
        ->and($breakdown->mediaType)->toBe(90);
});

it('allows boundary values 0 and 100', function () {
    $breakdown = PredictionBreakdown::create(0, 100, 0, 100, 50);

    expect($breakdown->contentSimilarity)->toBe(0)
        ->and($breakdown->timing)->toBe(100);
});

it('rejects negative content similarity', function () {
    PredictionBreakdown::create(-1, 50, 50, 50, 50);
})->throws(InvalidPredictionBreakdownException::class);

it('rejects score above 100', function () {
    PredictionBreakdown::create(50, 101, 50, 50, 50);
})->throws(InvalidPredictionBreakdownException::class);

it('rejects negative hashtags score', function () {
    PredictionBreakdown::create(50, 50, -10, 50, 50);
})->throws(InvalidPredictionBreakdownException::class);

it('rejects length score above 100', function () {
    PredictionBreakdown::create(50, 50, 50, 200, 50);
})->throws(InvalidPredictionBreakdownException::class);

it('rejects media type score above 100', function () {
    PredictionBreakdown::create(50, 50, 50, 50, 150);
})->throws(InvalidPredictionBreakdownException::class);

it('round-trips through toArray and fromArray', function () {
    $original = PredictionBreakdown::create(85, 72, 63, 91, 78);
    $restored = PredictionBreakdown::fromArray($original->toArray());

    expect($restored->contentSimilarity)->toBe($original->contentSimilarity)
        ->and($restored->timing)->toBe($original->timing)
        ->and($restored->hashtags)->toBe($original->hashtags)
        ->and($restored->length)->toBe($original->length)
        ->and($restored->mediaType)->toBe($original->mediaType);
});

it('serializes to array with snake_case keys', function () {
    $breakdown = PredictionBreakdown::create(80, 70, 60, 75, 90);

    expect($breakdown->toArray())->toBe([
        'content_similarity' => 80,
        'timing' => 70,
        'hashtags' => 60,
        'length' => 75,
        'media_type' => 90,
    ]);
});
