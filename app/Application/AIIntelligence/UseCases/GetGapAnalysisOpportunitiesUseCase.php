<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GapAnalysisOpportunitiesOutput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisOpportunitiesInput;
use App\Application\AIIntelligence\Exceptions\GapAnalysisNotFoundException;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetGapAnalysisOpportunitiesUseCase
{
    public function __construct(
        private readonly ContentGapAnalysisRepositoryInterface $gapAnalysisRepository,
    ) {}

    public function execute(GetGapAnalysisOpportunitiesInput $input): GapAnalysisOpportunitiesOutput
    {
        $analysisId = Uuid::fromString($input->analysisId);

        $analysis = $this->gapAnalysisRepository->findById($analysisId);

        if ($analysis === null) {
            throw new GapAnalysisNotFoundException;
        }

        if ((string) $analysis->organizationId !== $input->organizationId) {
            throw new GapAnalysisNotFoundException;
        }

        $actionable = $analysis->getActionableOpportunities($input->minScore);

        return new GapAnalysisOpportunitiesOutput(
            opportunities: $actionable,
            totalGaps: count($analysis->gaps),
            actionableOpportunities: count($actionable),
        );
    }
}
