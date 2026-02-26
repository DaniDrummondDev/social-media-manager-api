<?php

declare(strict_types=1);

use App\Domain\SocialListening\ValueObjects\Sentiment;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;

it('creates with counts', function () {
    $breakdown = SentimentBreakdown::create(10, 5, 3);

    expect($breakdown->positive)->toBe(10)
        ->and($breakdown->neutral)->toBe(5)
        ->and($breakdown->negative)->toBe(3);
});

it('creates empty', function () {
    $breakdown = SentimentBreakdown::empty();

    expect($breakdown->positive)->toBe(0)
        ->and($breakdown->neutral)->toBe(0)
        ->and($breakdown->negative)->toBe(0);
});

it('calculates total', function () {
    $breakdown = SentimentBreakdown::create(10, 5, 3);

    expect($breakdown->total())->toBe(18);
});

it('returns dominant sentiment as positive', function () {
    $breakdown = SentimentBreakdown::create(10, 5, 3);

    expect($breakdown->dominantSentiment())->toBe(Sentiment::Positive);
});

it('returns dominant sentiment as negative', function () {
    $breakdown = SentimentBreakdown::create(2, 3, 15);

    expect($breakdown->dominantSentiment())->toBe(Sentiment::Negative);
});

it('returns dominant sentiment as neutral', function () {
    $breakdown = SentimentBreakdown::create(3, 10, 5);

    expect($breakdown->dominantSentiment())->toBe(Sentiment::Neutral);
});

it('returns null dominant sentiment when empty', function () {
    $breakdown = SentimentBreakdown::empty();

    expect($breakdown->dominantSentiment())->toBeNull();
});

it('calculates negative percentage', function () {
    $breakdown = SentimentBreakdown::create(5, 5, 10);

    expect($breakdown->negativePercentage())->toBe(50.0);
});

it('returns zero negative percentage when empty', function () {
    $breakdown = SentimentBreakdown::empty();

    expect($breakdown->negativePercentage())->toBe(0.0);
});

it('converts from/to array', function () {
    $data = [
        'positive' => 10,
        'neutral' => 5,
        'negative' => 3,
    ];

    $breakdown = SentimentBreakdown::fromArray($data);

    expect($breakdown->positive)->toBe(10)
        ->and($breakdown->neutral)->toBe(5)
        ->and($breakdown->negative)->toBe(3)
        ->and($breakdown->toArray())->toBe($data);
});
