<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GetPredictionsInput;
use App\Application\AIIntelligence\DTOs\PredictionSummaryOutput;
use App\Domain\AIIntelligence\Entities\PerformancePrediction;
use App\Domain\AIIntelligence\Repositories\PerformancePredictionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetPredictionsUseCase
{
    public function __construct(
        private readonly PerformancePredictionRepositoryInterface $predictionRepository,
    ) {}

    /**
     * @return array<PredictionSummaryOutput>
     */
    public function execute(GetPredictionsInput $input): array
    {
        $contentId = Uuid::fromString($input->contentId);
        $predictions = $this->predictionRepository->findByContentId($contentId);

        return array_values(array_map(
            fn (PerformancePrediction $p) => PredictionSummaryOutput::fromEntity($p),
            array_filter(
                $predictions,
                fn (PerformancePrediction $p) => (string) $p->organizationId === $input->organizationId,
            ),
        ));
    }
}
