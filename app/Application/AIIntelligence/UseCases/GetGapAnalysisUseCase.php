<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\GapAnalysisOutput;
use App\Application\AIIntelligence\DTOs\GetGapAnalysisInput;
use App\Application\AIIntelligence\Exceptions\GapAnalysisNotFoundException;
use App\Domain\AIIntelligence\Repositories\ContentGapAnalysisRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetGapAnalysisUseCase
{
    public function __construct(
        private readonly ContentGapAnalysisRepositoryInterface $gapAnalysisRepository,
    ) {}

    public function execute(GetGapAnalysisInput $input): GapAnalysisOutput
    {
        $analysisId = Uuid::fromString($input->analysisId);

        $analysis = $this->gapAnalysisRepository->findById($analysisId);

        if ($analysis === null) {
            throw new GapAnalysisNotFoundException;
        }

        if ((string) $analysis->organizationId !== $input->organizationId) {
            throw new GapAnalysisNotFoundException;
        }

        return GapAnalysisOutput::fromEntity($analysis);
    }
}
