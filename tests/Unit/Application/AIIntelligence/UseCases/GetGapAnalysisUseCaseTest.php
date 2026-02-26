<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GapAnalysisOutput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisInput;
use App\Application\AIIntelligence\Exceptions\GapAnalysisNotFoundException;
use App\Application\AIIntelligence\UseCases\GetGapAnalysisUseCase;
use App\Domain\AIIntelligence\Entities\ContentGapAnalysis;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\GapAnalysisStatus;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->gapAnalysisRepository = Mockery::mock(ContentGapAnalysisRepositoryInterface::class);
    $this->useCase = new GetGapAnalysisUseCase($this->gapAnalysisRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('returns GapAnalysisOutput when found', function () {
    $analysisId = Uuid::generate();

    $analysis = ContentGapAnalysis::reconstitute(
        id: $analysisId,
        organizationId: Uuid::fromString($this->orgId),
        competitorQueryIds: ['query-1'],
        analysisPeriodStart: new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: new DateTimeImmutable,
        ourTopics: [['topic' => 'Tech', 'frequency' => 10, 'avg_engagement' => 4.5]],
        competitorTopics: [],
        gaps: [['topic' => 'AI', 'opportunity_score' => 85, 'competitor_count' => 2, 'recommendation' => 'Go']],
        opportunities: [],
        status: GapAnalysisStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn($analysis);

    $input = new GetGapAnalysisInput(
        organizationId: $this->orgId,
        analysisId: (string) $analysisId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GapAnalysisOutput::class)
        ->and($output->id)->toBe((string) $analysisId)
        ->and($output->status)->toBe('generated')
        ->and($output->gaps)->toHaveCount(1);
});

it('throws GapAnalysisNotFoundException when not found', function () {
    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new GetGapAnalysisInput(
        organizationId: $this->orgId,
        analysisId: (string) Uuid::generate(),
    );

    $this->useCase->execute($input);
})->throws(GapAnalysisNotFoundException::class);

it('throws GapAnalysisNotFoundException when organization mismatch', function () {
    $analysis = ContentGapAnalysis::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        competitorQueryIds: [],
        analysisPeriodStart: new DateTimeImmutable('-30 days'),
        analysisPeriodEnd: new DateTimeImmutable,
        ourTopics: [],
        competitorTopics: [],
        gaps: [],
        opportunities: [],
        status: GapAnalysisStatus::Generated,
        generatedAt: new DateTimeImmutable,
        expiresAt: new DateTimeImmutable('+7 days'),
        createdAt: new DateTimeImmutable,
    );

    $this->gapAnalysisRepository->shouldReceive('findById')->once()->andReturn($analysis);

    $input = new GetGapAnalysisInput(
        organizationId: $this->orgId,
        analysisId: (string) $analysis->id,
    );

    $this->useCase->execute($input);
})->throws(GapAnalysisNotFoundException::class);
