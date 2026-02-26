<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AudienceInsightOutput;
use App\Application\AIIntelligence\DTOs\ListAudienceInsightsInput;
use App\Domain\AIIntelligence\Repositories\AudienceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\InsightType;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListAudienceInsightsUseCase
{
    public function __construct(
        private readonly AudienceInsightRepositoryInterface $insightRepository,
    ) {}

    /**
     * @return array<AudienceInsightOutput>
     */
    public function execute(ListAudienceInsightsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);

        if ($input->type !== null) {
            $type = InsightType::from($input->type);
            $socialAccountId = $input->socialAccountId !== null
                ? Uuid::fromString($input->socialAccountId)
                : null;

            $insight = $this->insightRepository->findByOrganizationAndType(
                organizationId: $organizationId,
                type: $type,
                socialAccountId: $socialAccountId,
            );

            if ($insight === null) {
                return [];
            }

            return [AudienceInsightOutput::fromEntity($insight)];
        }

        $insights = $this->insightRepository->findActiveByOrganization($organizationId);

        return array_map(
            fn ($insight) => AudienceInsightOutput::fromEntity($insight),
            $insights,
        );
    }
}
