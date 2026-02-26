<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidEngagementPatternException;
use App\Domain\AIIntelligence\ValueObjects\EngagementPattern;

it('creates a valid engagement pattern', function () {
    $pattern = EngagementPattern::create(150, 30, 10, ['reel', 'carousel']);

    expect($pattern->avgLikes)->toBe(150)
        ->and($pattern->avgComments)->toBe(30)
        ->and($pattern->avgShares)->toBe(10)
        ->and($pattern->bestContentTypes)->toBe(['reel', 'carousel']);
});

it('rejects negative avg likes', function () {
    EngagementPattern::create(-1, 0, 0, []);
})->throws(InvalidEngagementPatternException::class);

it('rejects negative avg comments', function () {
    EngagementPattern::create(0, -5, 0, []);
})->throws(InvalidEngagementPatternException::class);

it('rejects negative avg shares', function () {
    EngagementPattern::create(0, 0, -1, []);
})->throws(InvalidEngagementPatternException::class);

it('allows zero values', function () {
    $pattern = EngagementPattern::create(0, 0, 0, []);

    expect($pattern->avgLikes)->toBe(0)
        ->and($pattern->avgComments)->toBe(0)
        ->and($pattern->avgShares)->toBe(0);
});

it('round-trips through toArray and fromArray', function () {
    $original = EngagementPattern::create(200, 50, 25, ['story', 'post']);
    $restored = EngagementPattern::fromArray($original->toArray());

    expect($restored->avgLikes)->toBe($original->avgLikes)
        ->and($restored->avgComments)->toBe($original->avgComments)
        ->and($restored->avgShares)->toBe($original->avgShares)
        ->and($restored->bestContentTypes)->toBe($original->bestContentTypes);
});

it('serializes to array with snake_case keys', function () {
    $pattern = EngagementPattern::create(100, 20, 5, ['video']);

    expect($pattern->toArray())->toBe([
        'avg_likes' => 100,
        'avg_comments' => 20,
        'avg_shares' => 5,
        'best_content_types' => ['video'],
    ]);
});
