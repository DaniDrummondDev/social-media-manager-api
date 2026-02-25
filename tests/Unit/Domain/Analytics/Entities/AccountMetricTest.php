<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\AccountMetric;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

it('creates account metric', function () {
    $accountId = Uuid::generate();
    $date = new DateTimeImmutable('2026-02-24');

    $metric = AccountMetric::create(
        socialAccountId: $accountId,
        provider: SocialProvider::Instagram,
        date: $date,
        followersCount: 10000,
        followersGained: 50,
        followersLost: 5,
        profileViews: 200,
        reach: 5000,
        impressions: 15000,
    );

    expect($metric->socialAccountId->equals($accountId))->toBeTrue()
        ->and($metric->provider)->toBe(SocialProvider::Instagram)
        ->and($metric->followersCount)->toBe(10000)
        ->and($metric->followersGained)->toBe(50)
        ->and($metric->followersLost)->toBe(5)
        ->and($metric->profileViews)->toBe(200)
        ->and($metric->reach)->toBe(5000)
        ->and($metric->impressions)->toBe(15000)
        ->and($metric->syncedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('updates metrics', function () {
    $metric = AccountMetric::create(
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::TikTok,
        date: new DateTimeImmutable('2026-02-24'),
        followersCount: 5000,
        followersGained: 20,
        followersLost: 2,
        profileViews: 100,
        reach: 3000,
        impressions: 8000,
    );

    $updated = $metric->updateMetrics(
        followersCount: 5100,
        followersGained: 120,
        followersLost: 20,
        profileViews: 300,
        reach: 6000,
        impressions: 16000,
    );

    expect($updated->followersCount)->toBe(5100)
        ->and($updated->followersGained)->toBe(120)
        ->and($updated->reach)->toBe(6000)
        ->and($updated->id->equals($metric->id))->toBeTrue()
        ->and($updated->syncedAt)->toBeGreaterThanOrEqual($metric->syncedAt);
});

it('reconstitutes account metric', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $metric = AccountMetric::reconstitute(
        id: $id,
        socialAccountId: Uuid::generate(),
        provider: SocialProvider::YouTube,
        date: $now,
        followersCount: 50000,
        followersGained: 200,
        followersLost: 10,
        profileViews: 1000,
        reach: 25000,
        impressions: 75000,
        syncedAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($metric->id->equals($id))->toBeTrue()
        ->and($metric->followersCount)->toBe(50000);
});
