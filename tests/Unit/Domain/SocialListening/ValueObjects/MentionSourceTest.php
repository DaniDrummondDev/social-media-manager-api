<?php

declare(strict_types=1);

use App\Domain\SocialListening\ValueObjects\MentionSource;

it('creates with all fields', function () {
    $source = MentionSource::create(
        platform: 'instagram',
        authorUsername: 'johndoe',
        authorDisplayName: 'John Doe',
        authorFollowerCount: 50000,
        profileUrl: 'https://instagram.com/johndoe',
    );

    expect($source->platform)->toBe('instagram')
        ->and($source->authorUsername)->toBe('johndoe')
        ->and($source->authorDisplayName)->toBe('John Doe')
        ->and($source->authorFollowerCount)->toBe(50000)
        ->and($source->profileUrl)->toBe('https://instagram.com/johndoe');
});

it('creates with nullable fields', function () {
    $source = MentionSource::create(
        platform: 'tiktok',
        authorUsername: 'janedoe',
        authorDisplayName: 'Jane Doe',
    );

    expect($source->authorFollowerCount)->toBeNull()
        ->and($source->profileUrl)->toBeNull();
});

it('detects influencer above threshold', function () {
    $source = MentionSource::create(
        platform: 'instagram',
        authorUsername: 'influencer',
        authorDisplayName: 'Big Influencer',
        authorFollowerCount: 100000,
    );

    expect($source->isInfluencer(50000))->toBeTrue();
});

it('does not detect influencer below threshold', function () {
    $source = MentionSource::create(
        platform: 'instagram',
        authorUsername: 'smalluser',
        authorDisplayName: 'Small User',
        authorFollowerCount: 500,
    );

    expect($source->isInfluencer(10000))->toBeFalse();
});

it('does not detect influencer when follower count is null', function () {
    $source = MentionSource::create(
        platform: 'instagram',
        authorUsername: 'unknown',
        authorDisplayName: 'Unknown User',
    );

    expect($source->isInfluencer(1000))->toBeFalse();
});

it('converts from/to array', function () {
    $data = [
        'platform' => 'instagram',
        'author_username' => 'johndoe',
        'author_display_name' => 'John Doe',
        'author_follower_count' => 50000,
        'profile_url' => 'https://instagram.com/johndoe',
    ];

    $source = MentionSource::fromArray($data);

    expect($source->platform)->toBe('instagram')
        ->and($source->authorUsername)->toBe('johndoe')
        ->and($source->authorDisplayName)->toBe('John Doe')
        ->and($source->authorFollowerCount)->toBe(50000)
        ->and($source->profileUrl)->toBe('https://instagram.com/johndoe')
        ->and($source->toArray())->toBe($data);
});
