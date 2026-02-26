<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\AudienceInsight;
use App\Domain\AIIntelligence\Events\AudienceInsightsRefreshed;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

function createAudienceInsight(array $overrides = []): AudienceInsight
{
    return AudienceInsight::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        socialAccountId: array_key_exists('socialAccountId', $overrides) ? $overrides['socialAccountId'] : null,
        insightType: $overrides['insightType'] ?? InsightType::PreferredTopics,
        insightData: $overrides['insightData'] ?? ['topics' => [['name' => 'Tech', 'score' => 0.85, 'comment_count' => 120]]],
        sourceCommentCount: $overrides['sourceCommentCount'] ?? 200,
        periodStart: $overrides['periodStart'] ?? new DateTimeImmutable('-30 days'),
        periodEnd: $overrides['periodEnd'] ?? new DateTimeImmutable,
        confidenceScore: array_key_exists('confidenceScore', $overrides) ? $overrides['confidenceScore'] : 0.85,
        userId: $overrides['userId'] ?? 'user-123',
    );
}

function reconstituteAudienceInsight(array $overrides = []): AudienceInsight
{
    return AudienceInsight::reconstitute(
        id: $overrides['id'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        socialAccountId: $overrides['socialAccountId'] ?? null,
        insightType: $overrides['insightType'] ?? InsightType::PreferredTopics,
        insightData: $overrides['insightData'] ?? ['topics' => []],
        sourceCommentCount: $overrides['sourceCommentCount'] ?? 100,
        periodStart: $overrides['periodStart'] ?? new DateTimeImmutable('-30 days'),
        periodEnd: $overrides['periodEnd'] ?? new DateTimeImmutable,
        confidenceScore: $overrides['confidenceScore'] ?? 0.75,
        generatedAt: $overrides['generatedAt'] ?? new DateTimeImmutable,
        expiresAt: $overrides['expiresAt'] ?? new DateTimeImmutable('+7 days'),
        createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
    );
}

it('creates an insight with domain event', function () {
    $insight = createAudienceInsight();

    expect($insight->insightType)->toBe(InsightType::PreferredTopics)
        ->and($insight->sourceCommentCount)->toBe(200)
        ->and($insight->confidenceScore)->toBe(0.85)
        ->and($insight->domainEvents)->toHaveCount(1)
        ->and($insight->domainEvents[0])->toBeInstanceOf(AudienceInsightsRefreshed::class);
});

it('creates with correct event payload', function () {
    $insight = createAudienceInsight([
        'insightType' => InsightType::SentimentTrends,
        'sourceCommentCount' => 500,
        'confidenceScore' => 0.92,
    ]);

    $event = $insight->domainEvents[0];

    expect($event->insightType)->toBe('sentiment_trends')
        ->and($event->sourceCommentCount)->toBe(500)
        ->and($event->confidenceScore)->toBe(0.92);
});

it('sets expires_at to 7 days from now', function () {
    $before = new DateTimeImmutable('+6 days');
    $insight = createAudienceInsight();
    $after = new DateTimeImmutable('+8 days');

    expect($insight->expiresAt)->toBeGreaterThan($before)
        ->and($insight->expiresAt)->toBeLessThan($after);
});

it('reconstitutes without domain events', function () {
    $insight = reconstituteAudienceInsight();

    expect($insight->domainEvents)->toHaveCount(0)
        ->and($insight->insightType)->toBe(InsightType::PreferredTopics);
});

it('reports expired when expiresAt is in the past', function () {
    $insight = reconstituteAudienceInsight([
        'expiresAt' => new DateTimeImmutable('-1 day'),
    ]);

    expect($insight->isExpired())->toBeTrue();
});

it('reports not expired when expiresAt is in the future', function () {
    $insight = reconstituteAudienceInsight([
        'expiresAt' => new DateTimeImmutable('+3 days'),
    ]);

    expect($insight->isExpired())->toBeFalse();
});

it('supports nullable social account id', function () {
    $insight = createAudienceInsight(['socialAccountId' => null]);

    expect($insight->socialAccountId)->toBeNull();
});

it('supports nullable confidence score', function () {
    $insight = createAudienceInsight(['confidenceScore' => null]);

    expect($insight->confidenceScore)->toBeNull();
});
