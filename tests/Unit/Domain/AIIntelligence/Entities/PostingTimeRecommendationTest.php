<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\PostingTimeRecommendation;
use App\Domain\AIIntelligence\Events\PostingTimesUpdated;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\AIIntelligence\ValueObjects\TimeSlotScore;
use App\Domain\AIIntelligence\ValueObjects\TopSlot;
use App\Domain\Shared\ValueObjects\Uuid;

function createRecommendation(array $overrides = []): PostingTimeRecommendation
{
    return PostingTimeRecommendation::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? null,
        provider: $overrides['provider'] ?? 'instagram',
        heatmap: $overrides['heatmap'] ?? [
            TimeSlotScore::create(1, 9, 85),
            TimeSlotScore::create(3, 14, 72),
        ],
        topSlots: $overrides['topSlots'] ?? [
            TopSlot::create(1, 9, 4.5, 30),
        ],
        worstSlots: $overrides['worstSlots'] ?? [
            TopSlot::create(0, 3, 0.2, 30),
        ],
        sampleSize: $overrides['sampleSize'] ?? 60,
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with PostingTimesUpdated event', function () {
    $recommendation = createRecommendation();

    expect($recommendation->domainEvents)->toHaveCount(1)
        ->and($recommendation->domainEvents[0])->toBeInstanceOf(PostingTimesUpdated::class)
        ->and($recommendation->provider)->toBe('instagram')
        ->and($recommendation->heatmap)->toHaveCount(2)
        ->and($recommendation->topSlots)->toHaveCount(1)
        ->and($recommendation->worstSlots)->toHaveCount(1);
});

it('computes confidence level High from sample size > 50', function () {
    $recommendation = createRecommendation(['sampleSize' => 60]);

    expect($recommendation->confidenceLevel)->toBe(ConfidenceLevel::High);
});

it('computes confidence level Medium from sample size 20-50', function () {
    $recommendation = createRecommendation(['sampleSize' => 35]);

    expect($recommendation->confidenceLevel)->toBe(ConfidenceLevel::Medium);
});

it('computes confidence level Low from sample size < 20', function () {
    $recommendation = createRecommendation(['sampleSize' => 10]);

    expect($recommendation->confidenceLevel)->toBe(ConfidenceLevel::Low);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $now = new DateTimeImmutable;

    $recommendation = PostingTimeRecommendation::reconstitute(
        id: $id,
        organizationId: $orgId,
        socialAccountId: null,
        provider: 'tiktok',
        heatmap: [TimeSlotScore::create(2, 10, 90)],
        topSlots: [TopSlot::create(2, 10, 5.0, 40)],
        worstSlots: [],
        sampleSize: 40,
        confidenceLevel: ConfidenceLevel::Medium,
        calculatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    expect($recommendation->id)->toEqual($id)
        ->and($recommendation->organizationId)->toEqual($orgId)
        ->and($recommendation->provider)->toBe('tiktok')
        ->and($recommendation->confidenceLevel)->toBe(ConfidenceLevel::Medium)
        ->and($recommendation->domainEvents)->toBeEmpty();
});

it('isExpired returns true when expiresAt is past', function () {
    $now = new DateTimeImmutable;

    $recommendation = PostingTimeRecommendation::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        socialAccountId: null,
        provider: 'instagram',
        heatmap: [],
        topSlots: [],
        worstSlots: [],
        sampleSize: 10,
        confidenceLevel: ConfidenceLevel::Low,
        calculatedAt: $now->modify('-8 days'),
        expiresAt: $now->modify('-1 day'),
        createdAt: $now->modify('-8 days'),
    );

    expect($recommendation->isExpired())->toBeTrue();
});

it('isExpired returns false when expiresAt is future', function () {
    $now = new DateTimeImmutable;

    $recommendation = PostingTimeRecommendation::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        socialAccountId: null,
        provider: 'instagram',
        heatmap: [],
        topSlots: [],
        worstSlots: [],
        sampleSize: 10,
        confidenceLevel: ConfidenceLevel::Low,
        calculatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
    );

    expect($recommendation->isExpired())->toBeFalse();
});

it('recalculates with new data and PostingTimesUpdated event', function () {
    $original = createRecommendation(['sampleSize' => 30]);

    $newHeatmap = [TimeSlotScore::create(5, 18, 95)];
    $newTopSlots = [TopSlot::create(5, 18, 6.0, 80)];
    $newWorstSlots = [TopSlot::create(0, 2, 0.1, 80)];

    $recalculated = $original->recalculate(
        heatmap: $newHeatmap,
        topSlots: $newTopSlots,
        worstSlots: $newWorstSlots,
        sampleSize: 80,
        userId: 'user-2',
    );

    expect($recalculated->domainEvents)->toHaveCount(1)
        ->and($recalculated->domainEvents[0])->toBeInstanceOf(PostingTimesUpdated::class)
        ->and($recalculated->heatmap)->toHaveCount(1)
        ->and($recalculated->sampleSize)->toBe(80)
        ->and($recalculated->confidenceLevel)->toBe(ConfidenceLevel::High);
});

it('recalculate preserves original id and createdAt', function () {
    $original = createRecommendation();

    $recalculated = $original->recalculate(
        heatmap: [TimeSlotScore::create(1, 10, 50)],
        topSlots: [],
        worstSlots: [],
        sampleSize: 25,
        userId: 'user-1',
    );

    expect($recalculated->id)->toEqual($original->id)
        ->and($recalculated->createdAt)->toEqual($original->createdAt)
        ->and($recalculated->calculatedAt)->not->toEqual($original->calculatedAt);
});
