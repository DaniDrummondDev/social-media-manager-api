<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GetPredictionAccuracyInput;
use App\Application\AIIntelligence\DTOs\PredictionAccuracyOutput;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetPredictionAccuracyUseCase
{
    private const int MIN_VALIDATIONS_FOR_METRICS = 10;

    public function __construct(
        private readonly PredictionValidationRepositoryInterface $validationRepository,
    ) {}

    public function execute(GetPredictionAccuracyInput $input): PredictionAccuracyOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        // RN-ALL-38: minimum 10 validations before showing metrics
        $count = $this->validationRepository->countByOrganization($organizationId);

        if ($count < self::MIN_VALIDATIONS_FOR_METRICS) {
            return new PredictionAccuracyOutput(
                meanAbsoluteError: 0.0,
                totalValidations: $count,
                message: sprintf(
                    'Insufficient data. Need %d validated predictions, have %d.',
                    self::MIN_VALIDATIONS_FOR_METRICS,
                    $count,
                ),
            );
        }

        $metrics = $this->validationRepository->calculateAccuracyMetrics($organizationId);

        return new PredictionAccuracyOutput(
            meanAbsoluteError: $metrics['mae'] ?? 0.0,
            totalValidations: $metrics['count'] ?? $count,
            message: 'Prediction accuracy metrics available.',
        );
    }
}
