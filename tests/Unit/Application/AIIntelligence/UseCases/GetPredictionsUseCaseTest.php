<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GetPredictionsInput;
use App\Application\AIIntelligence\DTOs\PredictionSummaryOutput;
use App\Application\AIIntelligence\UseCases\GetPredictionsUseCase;
use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->predictionRepository = Mockery::mock(PerformancePredictionRepositoryInterface::class);
    $this->useCase = new GetPredictionsUseCase($this->predictionRepository);
    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->contentId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
});

it('returns predictions for a content', function () {
    $orgId = Uuid::fromString($this->orgId);
    $contentId = Uuid::fromString($this->contentId);

    $predictions = [
        PerformancePrediction::reconstitute(
            id: Uuid::generate(),
            organizationId: $orgId,
            contentId: $contentId,
            provider: 'instagram',
            overallScore: PredictionScore::create(75),
            breakdown: PredictionBreakdown::create(80, 70, 60, 75, 90),
            similarContentIds: null,
            recommendations: [],
            modelVersion: 'v1',
            createdAt: new DateTimeImmutable,
        ),
        PerformancePrediction::reconstitute(
            id: Uuid::generate(),
            organizationId: $orgId,
            contentId: $contentId,
            provider: 'tiktok',
            overallScore: PredictionScore::create(60),
            breakdown: PredictionBreakdown::create(50, 50, 50, 50, 50),
            similarContentIds: null,
            recommendations: [],
            modelVersion: 'v1',
            createdAt: new DateTimeImmutable,
        ),
    ];

    $this->predictionRepository->shouldReceive('findByContentId')
        ->once()
        ->andReturn($predictions);

    $input = new GetPredictionsInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $outputs = $this->useCase->execute($input);

    expect($outputs)->toHaveCount(2)
        ->and($outputs[0])->toBeInstanceOf(PredictionSummaryOutput::class)
        ->and($outputs[0]->provider)->toBe('instagram')
        ->and($outputs[0]->overallScore)->toBe(75)
        ->and($outputs[1]->provider)->toBe('tiktok');
});

it('returns empty array when no predictions found', function () {
    $this->predictionRepository->shouldReceive('findByContentId')
        ->once()
        ->andReturn([]);

    $input = new GetPredictionsInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $outputs = $this->useCase->execute($input);

    expect($outputs)->toBeEmpty();
});

it('filters out predictions from other organizations', function () {
    $contentId = Uuid::fromString($this->contentId);
    $otherOrgId = Uuid::generate();

    $predictions = [
        PerformancePrediction::reconstitute(
            id: Uuid::generate(),
            organizationId: Uuid::fromString($this->orgId),
            contentId: $contentId,
            provider: 'instagram',
            overallScore: PredictionScore::create(80),
            breakdown: PredictionBreakdown::create(80, 80, 80, 80, 80),
            similarContentIds: null,
            recommendations: [],
            modelVersion: 'v1',
            createdAt: new DateTimeImmutable,
        ),
        PerformancePrediction::reconstitute(
            id: Uuid::generate(),
            organizationId: $otherOrgId,
            contentId: $contentId,
            provider: 'tiktok',
            overallScore: PredictionScore::create(90),
            breakdown: PredictionBreakdown::create(90, 90, 90, 90, 90),
            similarContentIds: null,
            recommendations: [],
            modelVersion: 'v1',
            createdAt: new DateTimeImmutable,
        ),
    ];

    $this->predictionRepository->shouldReceive('findByContentId')
        ->once()
        ->andReturn($predictions);

    $input = new GetPredictionsInput(
        organizationId: $this->orgId,
        contentId: $this->contentId,
    );

    $outputs = $this->useCase->execute($input);

    expect($outputs)->toHaveCount(1)
        ->and($outputs[0]->provider)->toBe('instagram');
});
