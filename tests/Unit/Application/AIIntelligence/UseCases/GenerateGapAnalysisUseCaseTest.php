<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GenerateGapAnalysisInput;
use App\Application\AIIntelligence\DTOs\GenerateGapAnalysisOutput;
use App\Application\AIIntelligence\UseCases\GenerateGapAnalysisUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;

beforeEach(function () {
    $this->gapAnalysisRepository = Mockery::mock(ContentGapAnalysisRepositoryInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);
    $this->useCase = new GenerateGapAnalysisUseCase(
        $this->gapAnalysisRepository,
        $this->eventDispatcher,
    );
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('creates a gap analysis with Generating status', function () {
    $this->gapAnalysisRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateGapAnalysisInput(
        organizationId: $this->orgId,
        userId: 'user-123',
        competitorQueryIds: ['query-1', 'query-2'],
        periodDays: 30,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(GenerateGapAnalysisOutput::class)
        ->and($output->analysisId)->toBeString()
        ->and($output->status)->toBe('generating')
        ->and($output->message)->toBe('Gap analysis is being generated.');
});

it('uses default 30 days period when not specified', function () {
    $this->gapAnalysisRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($analysis) {
            $days = (int) $analysis->analysisPeriodStart->diff($analysis->analysisPeriodEnd)->days;
            return $days === 30;
        });
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateGapAnalysisInput(
        organizationId: $this->orgId,
        userId: 'user-123',
        competitorQueryIds: ['query-1'],
    );

    $this->useCase->execute($input);
});

it('respects custom period days', function () {
    $this->gapAnalysisRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($analysis) {
            $days = (int) $analysis->analysisPeriodStart->diff($analysis->analysisPeriodEnd)->days;
            return $days === 60;
        });
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $input = new GenerateGapAnalysisInput(
        organizationId: $this->orgId,
        userId: 'user-123',
        competitorQueryIds: ['query-1'],
        periodDays: 60,
    );

    $this->useCase->execute($input);
});
