<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\ValueObjects\PredictionRecommendation;

it('creates a valid recommendation', function () {
    $rec = PredictionRecommendation::create('timing', 'Post in the morning for best engagement', 'high');

    expect($rec->type)->toBe('timing')
        ->and($rec->message)->toBe('Post in the morning for best engagement')
        ->and($rec->impactEstimate)->toBe('high');
});

it('round-trips through toArray and fromArray', function () {
    $original = PredictionRecommendation::create('hashtag', 'Add trending hashtags', 'medium');
    $restored = PredictionRecommendation::fromArray($original->toArray());

    expect($restored->type)->toBe($original->type)
        ->and($restored->message)->toBe($original->message)
        ->and($restored->impactEstimate)->toBe($original->impactEstimate);
});

it('serializes to array with snake_case keys', function () {
    $rec = PredictionRecommendation::create('length', 'Shorten your caption', 'low');

    expect($rec->toArray())->toBe([
        'type' => 'length',
        'message' => 'Shorten your caption',
        'impact_estimate' => 'low',
    ]);
});
