<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\Contracts\PredictionValidatorInterface;
use App\Application\AIIntelligence\DTOs\PredictionValidationOutput;
use App\Application\AIIntelligence\DTOs\ValidatePredictionInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\PredictionValidation;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\AIIntelligence\Repositories\PredictionValidationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;
use DomainException;

final class ValidatePredictionUseCase
{
    public function __construct(
        private readonly PredictionValidationRepositoryInterface $validationRepository,
        private readonly PerformancePredictionRepositoryInterface $predictionRepository,
        private readonly PredictionValidatorInterface $predictionValidator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ValidatePredictionInput $input): PredictionValidationOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $predictionId = Uuid::fromString($input->predictionId);
        $contentId = Uuid::fromString($input->contentId);

        // RN-ALL: one validation per prediction
        $existing = $this->validationRepository->findByPredictionId($predictionId);
        if ($existing !== null) {
            throw new DomainException("Prediction already validated: {$input->predictionId}");
        }

        // Get original prediction
        $prediction = $this->predictionRepository->findById($predictionId);
        if ($prediction === null) {
            throw new DomainException("Prediction not found: {$input->predictionId}");
        }

        // RN-ALL-33: normalize against org's own historical distribution
        $actualNormalizedScore = $this->predictionValidator->normalizeEngagementRate(
            organizationId: $input->organizationId,
            provider: $input->provider,
            engagementRate: $input->actualEngagementRate,
        );

        $validation = PredictionValidation::create(
            organizationId: $organizationId,
            predictionId: $predictionId,
            contentId: $contentId,
            provider: $input->provider,
            predictedScore: $prediction->overallScore->value,
            actualEngagementRate: $input->actualEngagementRate,
            actualNormalizedScore: $actualNormalizedScore,
            metricsSnapshot: $input->metricsSnapshot,
            metricsCapturedAt: new DateTimeImmutable($input->metricsCapturedAt),
            userId: $input->userId,
        );

        $this->validationRepository->create($validation);
        $this->eventDispatcher->dispatch(...$validation->domainEvents);

        return PredictionValidationOutput::fromEntity($validation);
    }
}
