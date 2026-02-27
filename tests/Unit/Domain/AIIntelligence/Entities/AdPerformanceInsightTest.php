<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Events\AdPerformanceAggregated;
use App\Domain\AIIntelligence\Exceptions\AdPerformanceInsightExpiredException;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;

function createInsight(array $overrides = []): AdPerformanceInsight
{
    return AdPerformanceInsight::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        adInsightType: $overrides['adInsightType'] ?? AdInsightType::BestAudiences,
        insightData: $overrides['insightData'] ?? ['audiences' => [['audience_id' => 'a1', 'avg_ctr' => 1.5]]],
        sampleSize: $overrides['sampleSize'] ?? 25,
        periodStart: $overrides['periodStart'] ?? new DateTimeImmutable('-7 days'),
        periodEnd: $overrides['periodEnd'] ?? new DateTimeImmutable,
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with AdPerformanceAggregated event', function () {
    $insight = createInsight(['sampleSize' => 25, 'adInsightType' => AdInsightType::BestAudiences]);

    expect($insight->adInsightType)->toBe(AdInsightType::BestAudiences)
        ->and($insight->sampleSize)->toBe(25)
        ->and($insight->domainEvents)->toHaveCount(1)
        ->and($insight->domainEvents[0])->toBeInstanceOf(AdPerformanceAggregated::class)
        ->and($insight->domainEvents[0]->adInsightType)->toBe('best_audiences')
        ->and($insight->domainEvents[0]->isRefresh)->toBeFalse();
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $insight = AdPerformanceInsight::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        adInsightType: AdInsightType::BestContentForAds,
        insightData: ['content_patterns' => []],
        sampleSize: 30,
        confidenceLevel: ConfidenceLevel::Medium,
        periodStart: $now->modify('-7 days'),
        periodEnd: $now,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
        updatedAt: $now,
    );

    expect($insight->id)->toEqual($id)
        ->and($insight->domainEvents)->toBeEmpty()
        ->and($insight->confidenceLevel)->toBe(ConfidenceLevel::Medium);
});

it('determines Low confidence for fewer than 20 samples', function () {
    $insight = createInsight(['sampleSize' => 10]);

    expect($insight->confidenceLevel)->toBe(ConfidenceLevel::Low);
});

it('determines Medium confidence for 20-50 samples', function () {
    $medium20 = createInsight(['sampleSize' => 20]);
    $medium50 = createInsight(['sampleSize' => 50]);

    expect($medium20->confidenceLevel)->toBe(ConfidenceLevel::Medium)
        ->and($medium50->confidenceLevel)->toBe(ConfidenceLevel::Medium);
});

it('determines High confidence for 51+ samples', function () {
    $insight = createInsight(['sampleSize' => 51]);

    expect($insight->confidenceLevel)->toBe(ConfidenceLevel::High);
});

it('sets TTL to 7 days from now', function () {
    $insight = createInsight();

    $daysDiff = $insight->generatedAt->diff($insight->expiresAt)->days;

    expect($daysDiff)->toBe(7);
});

it('isExpired returns false for fresh insight', function () {
    $insight = createInsight();

    expect($insight->isExpired())->toBeFalse();
});

it('isExpired returns true for expired insight', function () {
    $now = new DateTimeImmutable;
    $past = $now->modify('-10 days');

    $insight = AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        adInsightType: AdInsightType::BestAudiences,
        insightData: [],
        sampleSize: 25,
        confidenceLevel: ConfidenceLevel::Medium,
        periodStart: $past->modify('-7 days'),
        periodEnd: $past,
        generatedAt: $past,
        expiresAt: $past->modify('+7 days'), // 3 days ago
        createdAt: $past,
        updatedAt: $past,
    );

    expect($insight->isExpired())->toBeTrue();
});

it('assertNotExpired throws when expired', function () {
    $past = (new DateTimeImmutable)->modify('-10 days');

    $insight = AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        adInsightType: AdInsightType::BestAudiences,
        insightData: [],
        sampleSize: 25,
        confidenceLevel: ConfidenceLevel::Medium,
        periodStart: $past->modify('-7 days'),
        periodEnd: $past,
        generatedAt: $past,
        expiresAt: $past->modify('+7 days'),
        createdAt: $past,
        updatedAt: $past,
    );

    $insight->assertNotExpired();
})->throws(AdPerformanceInsightExpiredException::class);

it('hasEnoughData returns false below 5', function () {
    expect(AdPerformanceInsight::hasEnoughData(4))->toBeFalse();
});

it('hasEnoughData returns true at 5 or more', function () {
    expect(AdPerformanceInsight::hasEnoughData(5))->toBeTrue()
        ->and(AdPerformanceInsight::hasEnoughData(100))->toBeTrue();
});

it('minBoostsRequired returns 5', function () {
    expect(AdPerformanceInsight::minBoostsRequired())->toBe(5);
});

it('refresh updates data and emits event with isRefresh true', function () {
    $insight = createInsight(['sampleSize' => 20]);

    $refreshed = $insight->refresh(
        insightData: ['audiences' => [['audience_id' => 'a2', 'avg_ctr' => 2.5]]],
        sampleSize: 60,
        periodStart: new DateTimeImmutable('-14 days'),
        periodEnd: new DateTimeImmutable,
        userId: 'user-2',
    );

    expect($refreshed->sampleSize)->toBe(60)
        ->and($refreshed->confidenceLevel)->toBe(ConfidenceLevel::High)
        ->and($refreshed->insightData['audiences'][0]['audience_id'])->toBe('a2')
        ->and($refreshed->domainEvents)->toHaveCount(1)
        ->and($refreshed->domainEvents[0])->toBeInstanceOf(AdPerformanceAggregated::class)
        ->and($refreshed->domainEvents[0]->isRefresh)->toBeTrue()
        ->and($refreshed->domainEvents[0]->confidenceLevel)->toBe('high');
});

it('refresh preserves id and organization, recomputes confidence and TTL', function () {
    $orgId = Uuid::generate();
    $insight = createInsight(['organizationId' => $orgId, 'sampleSize' => 10]);

    $refreshed = $insight->refresh(
        insightData: [],
        sampleSize: 30,
        periodStart: new DateTimeImmutable('-7 days'),
        periodEnd: new DateTimeImmutable,
        userId: 'user-1',
    );

    expect($refreshed->id)->toEqual($insight->id)
        ->and($refreshed->organizationId)->toEqual($orgId)
        ->and($refreshed->createdAt)->toEqual($insight->createdAt)
        ->and($refreshed->confidenceLevel)->toBe(ConfidenceLevel::Medium)
        ->and($refreshed->generatedAt->diff($refreshed->expiresAt)->days)->toBe(7);
});
