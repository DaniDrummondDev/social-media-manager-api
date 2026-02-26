<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Events\PredictionCalculated;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionRecommendation;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\ValueObjects\Uuid;

function createPrediction(array $overrides = []): PerformancePrediction
{
    return PerformancePrediction::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        contentId: $overrides['contentId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? 'instagram',
        overallScore: $overrides['overallScore'] ?? PredictionScore::create(75),
        breakdown: $overrides['breakdown'] ?? PredictionBreakdown::create(80, 70, 60, 75, 90),
        similarContentIds: array_key_exists('similarContentIds', $overrides) ? $overrides['similarContentIds'] : ['id-1', 'id-2'],
        recommendations: $overrides['recommendations'] ?? [
            PredictionRecommendation::create('timing', 'Post in the morning', 'high'),
        ],
        modelVersion: $overrides['modelVersion'] ?? 'v1',
        userId: $overrides['userId'] ?? 'user-1',
    );
}

it('creates a prediction with domain event', function () {
    $prediction = createPrediction();

    expect($prediction->provider)->toBe('instagram')
        ->and($prediction->overallScore->value)->toBe(75)
        ->and($prediction->modelVersion)->toBe('v1')
        ->and($prediction->domainEvents)->toHaveCount(1)
        ->and($prediction->domainEvents[0])->toBeInstanceOf(PredictionCalculated::class);
});

it('creates a prediction with correct breakdown', function () {
    $breakdown = PredictionBreakdown::create(90, 80, 70, 60, 50);
    $prediction = createPrediction(['breakdown' => $breakdown]);

    expect($prediction->breakdown->contentSimilarity)->toBe(90)
        ->and($prediction->breakdown->timing)->toBe(80)
        ->and($prediction->breakdown->hashtags)->toBe(70)
        ->and($prediction->breakdown->length)->toBe(60)
        ->and($prediction->breakdown->mediaType)->toBe(50);
});

it('creates a prediction with null similar content ids', function () {
    $prediction = createPrediction(['similarContentIds' => null]);

    expect($prediction->similarContentIds)->toBeNull();
});

it('creates a prediction with multiple recommendations', function () {
    $recommendations = [
        PredictionRecommendation::create('timing', 'Post at 9am', 'high'),
        PredictionRecommendation::create('hashtag', 'Use #tech', 'medium'),
        PredictionRecommendation::create('length', 'Shorten caption', 'low'),
    ];

    $prediction = createPrediction(['recommendations' => $recommendations]);

    expect($prediction->recommendations)->toHaveCount(3)
        ->and($prediction->recommendations[0]->type)->toBe('timing')
        ->and($prediction->recommendations[2]->impactEstimate)->toBe('low');
});

it('reconstitutes without domain events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $contentId = Uuid::generate();

    $prediction = PerformancePrediction::reconstitute(
        id: $id,
        organizationId: $orgId,
        contentId: $contentId,
        provider: 'tiktok',
        overallScore: PredictionScore::create(88),
        breakdown: PredictionBreakdown::create(90, 85, 80, 92, 95),
        similarContentIds: ['abc'],
        recommendations: [],
        modelVersion: 'v2',
        createdAt: new DateTimeImmutable('2026-01-15T10:00:00+00:00'),
    );

    expect($prediction->domainEvents)->toBeEmpty()
        ->and((string) $prediction->id)->toBe((string) $id)
        ->and((string) $prediction->organizationId)->toBe((string) $orgId)
        ->and((string) $prediction->contentId)->toBe((string) $contentId)
        ->and($prediction->provider)->toBe('tiktok')
        ->and($prediction->overallScore->value)->toBe(88)
        ->and($prediction->modelVersion)->toBe('v2');
});

it('emits PredictionCalculated event with correct data', function () {
    $orgId = Uuid::generate();
    $contentId = Uuid::generate();

    $prediction = PerformancePrediction::create(
        organizationId: $orgId,
        contentId: $contentId,
        provider: 'youtube',
        overallScore: PredictionScore::create(60),
        breakdown: PredictionBreakdown::create(50, 50, 50, 50, 50),
        similarContentIds: null,
        recommendations: [],
        modelVersion: 'v1',
        userId: 'user-42',
    );

    /** @var PredictionCalculated $event */
    $event = $prediction->domainEvents[0];

    expect($event->organizationId)->toBe((string) $orgId)
        ->and($event->userId)->toBe('user-42')
        ->and($event->contentId)->toBe((string) $contentId)
        ->and($event->provider)->toBe('youtube')
        ->and($event->overallScore)->toBe(60);
});
