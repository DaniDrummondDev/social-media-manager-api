<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\ContentMetric;
use App\Domain\Analytics\Entities\ContentMetricSnapshot;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates snapshot copying metrics from ContentMetric', function () {
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
        views: 800,
        watchTimeSeconds: 3600,
        organizationId: (string) Uuid::generate(),
        userId: (string) Uuid::generate(),
    );

    $snapshot = ContentMetricSnapshot::create($metric->id, $metric);

    expect($snapshot->contentMetricId->equals($metric->id))->toBeTrue()
        ->and($snapshot->impressions)->toBe(1000)
        ->and($snapshot->reach)->toBe(500)
        ->and($snapshot->likes)->toBe(50)
        ->and($snapshot->comments)->toBe(10)
        ->and($snapshot->shares)->toBe(5)
        ->and($snapshot->saves)->toBe(3)
        ->and($snapshot->clicks)->toBe(20)
        ->and($snapshot->views)->toBe(800)
        ->and($snapshot->watchTimeSeconds)->toBe(3600)
        ->and($snapshot->engagementRate)->toBe($metric->engagementRate)
        ->and($snapshot->capturedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('reconstitutes snapshot', function () {
    $id = Uuid::generate();
    $metricId = Uuid::generate();
    $now = new DateTimeImmutable;

    $snapshot = ContentMetricSnapshot::reconstitute(
        id: $id,
        contentMetricId: $metricId,
        impressions: 500,
        reach: 300,
        likes: 30,
        comments: 10,
        shares: 5,
        saves: 2,
        clicks: 15,
        views: null,
        watchTimeSeconds: null,
        engagementRate: 15.6667,
        capturedAt: $now,
    );

    expect($snapshot->id->equals($id))->toBeTrue()
        ->and($snapshot->contentMetricId->equals($metricId))->toBeTrue()
        ->and($snapshot->views)->toBeNull()
        ->and($snapshot->capturedAt)->toEqual($now);
});
