<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\BestTimesHeatmapOutput;
use App\Application\AIIntelligence\DTOs\GetBestTimesInput;
use App\Domain\AIIntelligence\Repositories\PostingTimeRecommendationRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetBestTimesHeatmapUseCase
{
    public function __construct(
        private readonly PostingTimeRecommendationRepositoryInterface $recommendationRepository,
    ) {}

    public function execute(GetBestTimesInput $input): ?BestTimesHeatmapOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $socialAccountId = $input->socialAccountId !== null
            ? Uuid::fromString($input->socialAccountId)
            : null;

        $recommendation = $this->recommendationRepository->findByOrganization(
            organizationId: $organizationId,
            provider: $input->provider,
            socialAccountId: $socialAccountId,
        );

        if ($recommendation === null) {
            return null;
        }

        return BestTimesHeatmapOutput::fromEntity($recommendation);
    }
}
