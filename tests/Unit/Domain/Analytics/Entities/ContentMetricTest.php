<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\Events\MetricsSynced;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates with engagement rate and emits MetricsSynced', function () {
    $metric = ContentMetric::create(
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 1000,
        reach: 500,
        likes: 50,
        comments: 10,
        shares: 5,
        saves: 3,
        clicks: 20,
        views: null,
        watchTimeSeconds: null,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    // (50+10+5+3)/500*100 = 13.6
    expect($metric->engagementRate)->toBe(13.6)
        ->and($metric->impressions)->toBe(1000)
        ->and($metric->reach)->toBe(500)
        ->and($metric->likes)->toBe(50)
        ->and($metric->provider)->toBe(SocialProvider::Instagram)
        ->and($metric->domainEvents)->toHaveCount(1)
        ->and($metric->domainEvents[0])->toBeInstanceOf(MetricsSynced::class);
});

it('calculates zero engagement rate when reach is zero', function () {
    $metric = ContentMetric::create(
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::TikTok,
        externalPostId: null,
        impressions: 0,
        reach: 0,
        likes: 0,
        comments: 0,
        shares: 0,
        saves: 0,
        clicks: 0,
        views: null,
        watchTimeSeconds: null,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    expect($metric->engagementRate)->toBe(0.0);
});

it('updates metrics and recalculates engagement rate', function () {
    $metric = ContentMetric::create(
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 100,
        reach: 100,
        likes: 10,
        comments: 5,
        shares: 3,
        saves: 2,
        clicks: 5,
        views: null,
        watchTimeSeconds: null,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    $updated = $metric->updateMetrics(
        impressions: 200,
        reach: 200,
        likes: 40,
        comments: 20,
        shares: 10,
        saves: 5,
        clicks: 15,
        views: 150,
        watchTimeSeconds: 3600,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    // (40+20+10+5)/200*100 = 37.5
    expect($updated->engagementRate)->toBe(37.5)
        ->and($updated->impressions)->toBe(200)
        ->and($updated->views)->toBe(150)
        ->and($updated->watchTimeSeconds)->toBe(3600)
        ->and($updated->domainEvents)->toHaveCount(2);
});

it('reconstitutes without events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $metric = ContentMetric::reconstitute(
        id: $id,
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::YouTube,
        externalPostId: 'yt-123',
        impressions: 500,
        reach: 300,
        likes: 30,
        comments: 10,
        shares: 5,
        saves: 2,
        clicks: 15,
        views: 400,
        watchTimeSeconds: 7200,
        engagementRate: 15.6667,
        syncedAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($metric->id->equals($id))->toBeTrue()
        ->and($metric->domainEvents)->toBeEmpty();
});

it('releases events', function () {
    $metric = ContentMetric::create(
        contentId: Uuid::generate(),
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::Instagram,
        externalPostId: 'ext-123',
        impressions: 100,
        reach: 100,
        likes: 10,
        comments: 5,
        shares: 3,
        saves: 2,
        clicks: 5,
        views: null,
        watchTimeSeconds: null,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    $released = $metric->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and($released->impressions)->toBe(100);
});
