<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AdPerformanceInsightOutput;
use App\Application\AIIntelligence\DTOs\GetAdPerformanceInsightsInput;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetAdPerformanceInsightsUseCase
{
    public function __construct(
        private readonly AdPerformanceInsightRepositoryInterface $insightRepository,
    ) {}

    /**
     * @return array<AdPerformanceInsightOutput>
     */
    public function execute(GetAdPerformanceInsightsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        if ($input->adInsightType !== null) {
            $type = AdInsightType::from($input->adInsightType);

            $insight = $this->insightRepository->findByOrganizationAndType($organizationId, $type);

            if ($insight === null) {
                return [];
            }

            return [AdPerformanceInsightOutput::fromEntity($insight)];
        }

        $insights = $this->insightRepository->findActiveByOrganization($organizationId);

        return array_map(
            fn ($insight) => AdPerformanceInsightOutput::fromEntity($insight),
            $insights,
        );
    }
}
