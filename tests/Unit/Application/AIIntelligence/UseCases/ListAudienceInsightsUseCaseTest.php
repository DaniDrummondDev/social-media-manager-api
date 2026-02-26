<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AudienceInsightOutput;
use App\Application\AIIntelligence\DTOs\ListAudienceInsightsInput;
use App\Application\AIIntelligence\UseCases\ListAudienceInsightsUseCase;
use App\Domain\AIIntelligence\Entities\AudienceInsight;
use App\Domain\AIIntelligence\Repositories\AudienceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->insightRepository = Mockery::mock(AudienceInsightRepositoryInterface::class);
    $this->useCase = new ListAudienceInsightsUseCase($this->insightRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns all active insights when no type filter', function () {
    $insight = AudienceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        insightType: InsightType::PreferredTopics,
        insightData: ['topics' => []],
        sourceCommentCount: 100,
        periodStart: new DateTimeImmutable('-30 days'),
        periodEnd: new DateTimeImmutable,
        confidenceScore: 0.8,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->insightRepository->shouldReceive('findActiveByOrganization')->once()->andReturn([$insight]);

    $input = new ListAudienceInsightsInput(organizationId: $this->orgId);
    $result = $this->useCase->execute($input);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(AudienceInsightOutput::class)
        ->and($result[0]->insightType)->toBe('preferred_topics');
});

it('returns filtered insight when type is provided', function () {
    $insight = AudienceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::fromString($this->orgId),
        socialAccountId: null,
        insightType: InsightType::SentimentTrends,
        insightData: ['trend' => []],
        sourceCommentCount: 50,
        periodStart: new DateTimeImmutable('-30 days'),
        periodEnd: new DateTimeImmutable,
        confidenceScore: 0.7,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->insightRepository->shouldReceive('findByOrganizationAndType')->once()->andReturn($insight);

    $input = new ListAudienceInsightsInput(organizationId: $this->orgId, type: 'sentiment_trends');
    $result = $this->useCase->execute($input);

    expect($result)->toHaveCount(1)
        ->and($result[0]->insightType)->toBe('sentiment_trends');
});

it('returns empty array when type filter has no match', function () {
    $this->insightRepository->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);

    $input = new ListAudienceInsightsInput(organizationId: $this->orgId, type: 'engagement_drivers');
    $result = $this->useCase->execute($input);

    expect($result)->toBe([]);
});

it('returns empty array when no active insights exist', function () {
    $this->insightRepository->shouldReceive('findActiveByOrganization')->once()->andReturn([]);

    $input = new ListAudienceInsightsInput(organizationId: $this->orgId);
    $result = $this->useCase->execute($input);

    expect($result)->toBe([]);
});
