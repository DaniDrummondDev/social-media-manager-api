<?php

declare(strict_types=1);

use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\AIIntelligence\Events\ContentGapsIdentified;
use App\Domain\AIIntelligence\Exceptions\GapAnalysisExpiredException;
use App\Domain\AIIntelligence\Exceptions\InvalidGapAnalysisStatusTransitionException;
use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

function createGapAnalysis(array $overrides = []): ContentGapAnalysis
{
    return ContentGapAnalysis::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        competitorQueryIds: $overrides['competitorQueryIds'] ?? ['query-1', 'query-2'],
        analysisPeriodStart: $overrides['analysisPeriodStart'] ?? new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: $overrides['analysisPeriodEnd'] ?? new DateTimeImmutable,
        userId: $overrides['userId'] ?? 'user-123',
    );
}

function reconstituteGapAnalysis(array $overrides = []): ContentGapAnalysis
{
    return ContentGapAnalysis::reconstitute(
        id: $overrides['id'] ?? Uuid::generate(),
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        competitorQueryIds: $overrides['competitorQueryIds'] ?? ['query-1'],
        analysisPeriodStart: $overrides['analysisPeriodStart'] ?? new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: $overrides['analysisPeriodEnd'] ?? new DateTimeImmutable,
        ourTopics: $overrides['ourTopics'] ?? [],
        competitorTopics: $overrides['competitorTopics'] ?? [],
        gaps: $overrides['gaps'] ?? [],
        opportunities: $overrides['opportunities'] ?? [],
        status: $overrides['status'] ?? GapAnalysisStatus::Generating,
        generatedAt: $overrides['generatedAt'] ?? new DateTimeImmutable,
        expiresAt: $overrides['expiresAt'] ?? new DateTimeImmutable('+7 days'),
        createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
    );
}

it('creates with Generating status and no domain events', function () {
    $analysis = createGapAnalysis();

    expect($analysis->status)->toBe(GapAnalysisStatus::Generating)
        ->and($analysis->ourTopics)->toBe([])
        ->and($analysis->competitorTopics)->toBe([])
        ->and($analysis->gaps)->toBe([])
        ->and($analysis->opportunities)->toBe([])
        ->and($analysis->domainEvents)->toHaveCount(0);
});

it('completes with data and fires ContentGapsIdentified event', function () {
    $analysis = createGapAnalysis();

    $gaps = [['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Create AI content']];
    $opportunities = [['topic' => 'AI', 'reason' => 'High demand', 'suggested_content_type' => 'tutorial', 'estimated_impact' => 'high']];

    $completed = $analysis->complete(
        ourTopics: [['topic' => 'Tech', 'frequency' => 10, 'avg_engagement' => 4.5]],
        competitorTopics: [['topic' => 'AI', 'source_competitor' => 'rival', 'frequency' => 20, 'avg_engagement' => 6.0]],
        gaps: $gaps,
        opportunities: $opportunities,
        userId: 'user-123',
    );

    expect($completed->status)->toBe(GapAnalysisStatus::Generated)
        ->and($completed->gaps)->toHaveCount(1)
        ->and($completed->opportunities)->toHaveCount(1)
        ->and($completed->domainEvents)->toHaveCount(1)
        ->and($completed->domainEvents[0])->toBeInstanceOf(ContentGapsIdentified::class)
        ->and($completed->domainEvents[0]->gapCount)->toBe(1)
        ->and($completed->domainEvents[0]->opportunityCount)->toBe(1);
});

it('cannot complete an already generated analysis', function () {
    $analysis = reconstituteGapAnalysis(['status' => GapAnalysisStatus::Generated]);

    $analysis->complete([], [], [], [], 'user-123');
})->throws(InvalidGapAnalysisStatusTransitionException::class);

it('marks as expired from Generated status', function () {
    $analysis = reconstituteGapAnalysis(['status' => GapAnalysisStatus::Generated]);

    $expired = $analysis->markExpired();

    expect($expired->status)->toBe(GapAnalysisStatus::Expired);
});

it('throws when marking already expired', function () {
    $analysis = reconstituteGapAnalysis(['status' => GapAnalysisStatus::Expired]);

    $analysis->markExpired();
})->throws(GapAnalysisExpiredException::class);

it('reports expired when expiresAt is in the past', function () {
    $analysis = reconstituteGapAnalysis(['expiresAt' => new DateTimeImmutable('-1 day')]);

    expect($analysis->isExpired())->toBeTrue();
});

it('reports not expired when expiresAt is in the future', function () {
    $analysis = reconstituteGapAnalysis(['expiresAt' => new DateTimeImmutable('+3 days')]);

    expect($analysis->isExpired())->toBeFalse();
});

it('returns actionable opportunities with default min score', function () {
    $analysis = reconstituteGapAnalysis([
        'status' => GapAnalysisStatus::Generated,
        'gaps' => [
            ['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Create AI content'],
            ['topic' => 'Crypto', 'opportunity_score' => 30, 'competitor_count' => 1, 'recommendation' => 'Maybe later'],
            ['topic' => 'Remote', 'opportunity_score' => 60, 'competitor_count' => 2, 'recommendation' => 'Explore remote work'],
        ],
    ]);

    $actionable = $analysis->getActionableOpportunities();

    expect($actionable)->toHaveCount(2)
        ->and($actionable[0]['topic'])->toBe('AI')
        ->and($actionable[1]['topic'])->toBe('Remote');
});

it('returns actionable opportunities with custom min score', function () {
    $analysis = reconstituteGapAnalysis([
        'status' => GapAnalysisStatus::Generated,
        'gaps' => [
            ['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Go'],
            ['topic' => 'Remote', 'opportunity_score' => 60, 'competitor_count' => 2, 'recommendation' => 'Maybe'],
        ],
    ]);

    $actionable = $analysis->getActionableOpportunities(minScore: 80);

    expect($actionable)->toHaveCount(1)
        ->and($actionable[0]['topic'])->toBe('AI');
});

it('reconstitutes without domain events', function () {
    $analysis = reconstituteGapAnalysis();

    expect($analysis->domainEvents)->toHaveCount(0);
});
