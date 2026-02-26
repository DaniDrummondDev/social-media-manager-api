<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Exceptions\InvalidContentFingerprintException;
use App\Domain\AIIntelligence\ValueObjects\ContentFingerprint;

it('creates a valid content fingerprint', function () {
    $fingerprint = ContentFingerprint::create(280, ['#tech', '#ai'], ['informative' => 0.6, 'casual' => 0.4], 3.5);

    expect($fingerprint->avgLength)->toBe(280)
        ->and($fingerprint->hashtagPatterns)->toBe(['#tech', '#ai'])
        ->and($fingerprint->toneDistribution)->toBe(['informative' => 0.6, 'casual' => 0.4])
        ->and($fingerprint->postingFrequency)->toBe(3.5);
});

it('rejects negative avg length', function () {
    ContentFingerprint::create(-1, [], [], 1.0);
})->throws(InvalidContentFingerprintException::class);

it('rejects negative posting frequency', function () {
    ContentFingerprint::create(100, [], [], -0.5);
})->throws(InvalidContentFingerprintException::class);

it('allows zero values', function () {
    $fingerprint = ContentFingerprint::create(0, [], [], 0.0);

    expect($fingerprint->avgLength)->toBe(0)
        ->and($fingerprint->postingFrequency)->toBe(0.0);
});

it('round-trips through toArray and fromArray', function () {
    $original = ContentFingerprint::create(350, ['#laravel'], ['professional' => 0.8], 5.0);
    $restored = ContentFingerprint::fromArray($original->toArray());

    expect($restored->avgLength)->toBe($original->avgLength)
        ->and($restored->hashtagPatterns)->toBe($original->hashtagPatterns)
        ->and($restored->toneDistribution)->toBe($original->toneDistribution)
        ->and($restored->postingFrequency)->toBe($original->postingFrequency);
});

it('serializes to array with snake_case keys', function () {
    $fingerprint = ContentFingerprint::create(200, ['#dev'], ['casual' => 1.0], 2.0);

    expect($fingerprint->toArray())->toBe([
        'avg_length' => 200,
        'hashtag_patterns' => ['#dev'],
        'tone_distribution' => ['casual' => 1.0],
        'posting_frequency' => 2.0,
    ]);
});
