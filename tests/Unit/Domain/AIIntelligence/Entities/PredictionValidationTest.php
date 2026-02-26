<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\PredictionValidation;
use App\Domain\AIIntelligence\Events\PredictionValidated;
use App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy;
use App\Domain\Shared\ValueObjects\Uuid;

function createValidation(array $overrides = []): PredictionValidation
{
    return PredictionValidation::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        predictionId: $overrides['predictionId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? 'instagram',
        predictedScore: $overrides['predictedScore'] ?? 80,
        actualEngagementRate: $overrides['actualEngagementRate'] ?? 3.5,
        actualNormalizedScore: $overrides['actualNormalizedScore'] ?? 75,
        metricsSnapshot: $overrides['metricsSnapshot'] ?? ['likes' => 100, 'comments' => 10],
        metricsCapturedAt: $overrides['metricsCapturedAt'] ?? new DateTimeImmutable,
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates with calculated accuracy and PredictionValidated event', function () {
    $validation = createValidation(['predictedScore' => 80, 'actualNormalizedScore' => 75]);

    expect($validation->accuracy)->not->toBeNull()
        ->and($validation->accuracy->absoluteError)->toBe(5)
        ->and($validation->accuracy->accuracyPercentage)->toBe(95.0)
        ->and($validation->predictedScore)->toBe(80)
        ->and($validation->actualNormalizedScore)->toBe(75)
        ->and($validation->actualEngagementRate)->toBe(3.5)
        ->and($validation->provider)->toBe('instagram')
        ->and($validation->domainEvents)->toHaveCount(1)
        ->and($validation->domainEvents[0])->toBeInstanceOf(PredictionValidated::class)
        ->and($validation->domainEvents[0]->absoluteError)->toBe(5);
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $validation = PredictionValidation::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        predictionId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: 'tiktok',
        predictedScore: 60,
        actualEngagementRate: 2.0,
        actualNormalizedScore: 55,
        accuracy: PredictionAccuracy::fromValues(5, 95.0),
        metricsSnapshot: [],
        validatedAt: $now,
        metricsCapturedAt: $now,
        createdAt: $now,
    );

    expect($validation->id)->toEqual($id)
        ->and($validation->provider)->toBe('tiktok')
        ->and($validation->domainEvents)->toBeEmpty();
});

it('isAccurate returns true for good prediction', function () {
    $validation = createValidation(['predictedScore' => 80, 'actualNormalizedScore' => 80]);

    expect($validation->isAccurate())->toBeTrue();
});

it('isAccurate returns false for bad prediction', function () {
    $validation = createValidation(['predictedScore' => 90, 'actualNormalizedScore' => 40]);

    expect($validation->isAccurate())->toBeFalse();
});

it('getGrade returns accuracy grade', function () {
    $validation = createValidation(['predictedScore' => 80, 'actualNormalizedScore' => 75]);

    expect($validation->getGrade())->toBe('A');
});

it('getGrade returns null when accuracy is null', function () {
    $now = new DateTimeImmutable;

    $validation = PredictionValidation::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        predictionId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: 'youtube',
        predictedScore: 50,
        actualEngagementRate: null,
        actualNormalizedScore: null,
        accuracy: null,
        metricsSnapshot: [],
        validatedAt: $now,
        metricsCapturedAt: $now,
        createdAt: $now,
    );

    expect($validation->getGrade())->toBeNull()
        ->and($validation->isAccurate())->toBeFalse();
});
