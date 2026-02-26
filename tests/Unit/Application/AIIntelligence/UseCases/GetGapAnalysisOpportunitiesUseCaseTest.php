<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GapAnalysisOpportunitiesOutput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisOpportunitiesInput;
use App\Application\AIIntelligence\Exceptions\GapAnalysisNotFoundException;
use App\Application\AIIntelligence\UseCases\GetGapAnalysisOpportunitiesUseCase;
use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

beforeEach(function () {
    $this->gapAnalysisRepository = Mockery::mock(ContentGapAnalysisRepositoryInterface::class);
    $this->useCase = new GetGapAnalysisOpportunitiesUseCase($this->gapAnalysisRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns actionable opportunities', function () {
    $analysisId = Uuid::generate();

    $analysis = ContentGapAnalysis::reconstitute(
        id: $analysisId,
        organizationId: Uuid::fromString($this->orgId),
        competitorQueryIds: ['query-1'],
        analysisPeriodStart: new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: new DateTimeImmutable,
        ourTopics: [],
        competitorTopics: [],
        gaps: [
            ['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Go'],
            ['topic' => 'Crypto', 'opportunity_score' => 30, 'competitor_count' => 1, 'recommendation' => 'Skip'],
            ['topic' => 'Remote', 'opportunity_score' => 60, 'competitor_count' => 2, 'recommendation' => 'Maybe'],
        ],
        opportunities: [],
        status: GapAnalysisStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn($analysis);

    $input = new GetGapAnalysisOpportunitiesInput(
        organizationId: $this->orgId,
        analysisId: (string) $analysisId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GapAnalysisOpportunitiesOutput::class)
        ->and($output->totalGaps)->toBe(3)
        ->and($output->actionableOpportunities)->toBe(2)
        ->and($output->opportunities)->toHaveCount(2)
        ->and($output->opportunities[0]['topic'])->toBe('AI')
        ->and($output->opportunities[1]['topic'])->toBe('Remote');
});

it('respects custom min score', function () {
    $analysisId = Uuid::generate();

    $analysis = ContentGapAnalysis::reconstitute(
        id: $analysisId,
        organizationId: Uuid::fromString($this->orgId),
        competitorQueryIds: [],
        analysisPeriodStart: new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: new DateTimeImmutable,
        ourTopics: [],
        competitorTopics: [],
        gaps: [
            ['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 3, 'recommendation' => 'Go'],
            ['topic' => 'Remote', 'opportunity_score' => 60, 'competitor_count' => 2, 'recommendation' => 'Maybe'],
        ],
        opportunities: [],
        status: GapAnalysisStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn($analysis);

    $input = new GetGapAnalysisOpportunitiesInput(
        organizationId: $this->orgId,
        analysisId: (string) $analysisId,
        minScore: 80,
    );

    $output = $this->useCase->execute($input);

    expect($output->actionableOpportunities)->toBe(1)
        ->and($output->opportunities[0]['topic'])->toBe('AI');
});

it('throws GapAnalysisNotFoundException when not found', function () {
    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new GetGapAnalysisOpportunitiesInput(
        organizationId: $this->orgId,
        analysisId: (string) Uuid::generate(),
    );

    $this->useCase->execute($input);
})->throws(GapAnalysisNotFoundException::class);
