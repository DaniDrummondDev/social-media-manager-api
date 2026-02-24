<?php

declare(strict_types=1);

use App\Domain\Campaign\Entities\ContentNetworkOverride;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates override with all fields', function () {
    $override = ContentNetworkOverride::create(
        contentId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        title: 'Instagram Title',
        body: 'Instagram body',
        hashtags: ['ig', 'post'],
        metadata: ['key' => 'value'],
    );

    expect($override->provider)->toBe(SocialProvider::Instagram)
        ->and($override->title)->toBe('Instagram Title')
        ->and($override->body)->toBe('Instagram body')
        ->and($override->hashtags)->toBe(['ig', 'post'])
        ->and($override->metadata)->toBe(['key' => 'value']);
});

it('creates override with nullable fields', function () {
    $override = ContentNetworkOverride::create(
        contentId: Uuid::generate(),
        provider: SocialProvider::TikTok,
    );

    expect($override->provider)->toBe(SocialProvider::TikTok)
        ->and($override->title)->toBeNull()
        ->and($override->body)->toBeNull()
        ->and($override->hashtags)->toBeNull()
        ->and($override->metadata)->toBeNull();
});

it('reconstitutes from database', function () {
    $now = new DateTimeImmutable;
    $override = ContentNetworkOverride::reconstitute(
        id: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: SocialProvider::YouTube,
        title: 'YT Title',
        body: null,
        hashtags: null,
        metadata: null,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($override->provider)->toBe(SocialProvider::YouTube)
        ->and($override->title)->toBe('YT Title');
});
