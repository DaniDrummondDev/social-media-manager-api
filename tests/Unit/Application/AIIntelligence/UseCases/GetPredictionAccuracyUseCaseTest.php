<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\GetPredictionAccuracyInput;
use App\Application\AIIntelligence\DTOs\PredictionAccuracyOutput;
use App\Application\AIIntelligence\UseCases\GetPredictionAccuracyUseCase;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->validationRepository = Mockery::mock(PredictionValidationRepositoryInterface::class);

    $this->useCase = new GetPredictionAccuracyUseCase(
        $this->validationRepository,
    );
});

it('returns insufficient data message when below 10 validations', function () {
    $this->validationRepository->shouldReceive('countByOrganization')->once()->andReturn(5);

    $output = $this->useCase->execute(new GetPredictionAccuracyInput(
        organizationId: (string) Uuid::generate(),
    ));

    expect($output)->toBeInstanceOf(PredictionAccuracyOutput::class)
        ->and($output->totalValidations)->toBe(5)
        ->and($output->meanAbsoluteError)->toBe(0.0)
        ->and($output->message)->toContain('Insufficient data');
});

it('returns accuracy metrics when sufficient validations exist', function () {
    $this->validationRepository->shouldReceive('countByOrganization')->once()->andReturn(15);
    $this->validationRepository->shouldReceive('calculateAccuracyMetrics')
        ->once()
        ->andReturn(['mae' => 8.5, 'count' => 15]);

    $output = $this->useCase->execute(new GetPredictionAccuracyInput(
        organizationId: (string) Uuid::generate(),
    ));

    expect($output->meanAbsoluteError)->toBe(8.5)
        ->and($output->totalValidations)->toBe(15)
        ->and($output->message)->toBe('Prediction accuracy metrics available.');
});

it('returns metrics with exactly 10 validations (boundary)', function () {
    $this->validationRepository->shouldReceive('countByOrganization')->once()->andReturn(10);
    $this->validationRepository->shouldReceive('calculateAccuracyMetrics')
        ->once()
        ->andReturn(['mae' => 12.0, 'count' => 10]);

    $output = $this->useCase->execute(new GetPredictionAccuracyInput(
        organizationId: (string) Uuid::generate(),
    ));

    expect($output->meanAbsoluteError)->toBe(12.0)
        ->and($output->totalValidations)->toBe(10);
});
