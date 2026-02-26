<?php

declare(strict_types=1);

use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;
use App\Application\AIIntelligence\DTOs\PredictionValidationOutput;
use App\Application\AIIntelligence\DTOs\ValidatePredictionInput;
use App\Application\AIIntelligence\UseCases\ValidatePredictionUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Entities\PredictionValidation;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\PredictionAccuracy;
use App\Domain\AIIntelligence\ValueObjects\PredictionBreakdown;
use App\Domain\AIIntelligence\ValueObjects\PredictionScore;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->validationRepository = Mockery::mock(PredictionValidationRepositoryInterface::class);
    $this->predictionRepository = Mockery::mock(PerformancePredictionRepositoryInterface::class);
    $this->predictionValidator = Mockery::mock(PredictionValidatorInterface::class);
    $this->eventDispatcher = Mockery::mock(EventDispatcherInterface::class);

    $this->useCase = new ValidatePredictionUseCase(
        $this->validationRepository,
        $this->predictionRepository,
        $this->predictionValidator,
        $this->eventDispatcher,
    );
});

it('validates prediction and returns output', function () {
    $predictionId = Uuid::generate();
    $now = new DateTimeImmutable;

    $prediction = PerformancePrediction::reconstitute(
        id: $predictionId,
        organizationId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: 'instagram',
        overallScore: PredictionScore::create(80),
        breakdown: PredictionBreakdown::create(
            contentSimilarity: 80,
            timing: 70,
            hashtags: 75,
            length: 85,
            mediaType: 90,
        ),
        similarContentIds: null,
        recommendations: [],
        modelVersion: 'v1',
        createdAt: $now,
    );

    $this->validationRepository->shouldReceive('findByPredictionId')->once()->andReturn(null);
    $this->predictionRepository->shouldReceive('findById')->once()->andReturn($prediction);
    $this->predictionValidator->shouldReceive('normalizeEngagementRate')->once()->andReturn(75);
    $this->validationRepository->shouldReceive('create')->once();
    $this->eventDispatcher->shouldReceive('dispatch')->once();

    $output = $this->useCase->execute(new ValidatePredictionInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        predictionId: (string) $predictionId,
        contentId: (string) Uuid::generate(),
        provider: 'instagram',
        actualEngagementRate: 3.5,
        metricsSnapshot: ['likes' => 100],
        metricsCapturedAt: $now->format('c'),
    ));

    expect($output)->toBeInstanceOf(PredictionValidationOutput::class)
        ->and($output->predictedScore)->toBe(80)
        ->and($output->actualNormalizedScore)->toBe(75)
        ->and($output->absoluteError)->toBe(5)
        ->and($output->grade)->toBe('A');
});

it('throws when prediction already validated', function () {
    // Create a real PredictionValidation entity (final class, cannot mock)
    $existingValidation = PredictionValidation::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        predictionId: Uuid::generate(),
        contentId: Uuid::generate(),
        provider: 'instagram',
        predictedScore: 80,
        actualEngagementRate: 3.5,
        actualNormalizedScore: 75,
        accuracy: PredictionAccuracy::fromValues(5, 95.0),
        metricsSnapshot: [],
        validatedAt: new DateTimeImmutable,
        metricsCapturedAt: new DateTimeImmutable,
        createdAt: new DateTimeImmutable,
    );

    $this->validationRepository->shouldReceive('findByPredictionId')
        ->once()
        ->andReturn($existingValidation);

    $this->useCase->execute(new ValidatePredictionInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        predictionId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
        provider: 'instagram',
        actualEngagementRate: 3.5,
        metricsSnapshot: [],
        metricsCapturedAt: (new DateTimeImmutable)->format('c'),
    ));
})->throws(DomainException::class, 'Prediction already validated');

it('throws when prediction not found', function () {
    $this->validationRepository->shouldReceive('findByPredictionId')->once()->andReturn(null);
    $this->predictionRepository->shouldReceive('findById')->once()->andReturn(null);

    $this->useCase->execute(new ValidatePredictionInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        predictionId: (string) Uuid::generate(),
        contentId: (string) Uuid::generate(),
        provider: 'instagram',
        actualEngagementRate: 3.5,
        metricsSnapshot: [],
        metricsCapturedAt: (new DateTimeImmutable)->format('c'),
    ));
})->throws(DomainException::class, 'Prediction not found');
