<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AdTargetingSuggestionsOutput;
use App\Application\AIIntelligence\DTOs\GetAdTargetingSuggestionsInput;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\AdIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Events\AdTargetingSuggested;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetAdTargetingSuggestionsUseCase
{
    public function __construct(
        private readonly AdIntelligenceProviderInterface $adIntelligenceProvider,
        private readonly AdPerformanceInsightRepositoryInterface $insightRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(GetAdTargetingSuggestionsInput $input): AdTargetingSuggestionsOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $contentId = Uuid::fromString($input->contentId);

        $insight = $this->insightRepository->findByOrganizationAndType(
            $organizationId,
            AdInsightType::BestAudiences,
        );

        $basedOnInsightType = $insight !== null
            ? $insight->adInsightType->value
            : 'none';

        $confidenceLevel = $insight !== null
            ? $insight->confidenceLevel->value
            : 'low';

        $suggestions = $this->adIntelligenceProvider->getTargetingSuggestions($organizationId, $contentId);

        $this->eventDispatcher->dispatch(
            new AdTargetingSuggested(
                aggregateId: $input->organizationId,
                organizationId: $input->organizationId,
                userId: $input->userId,
                suggestionCount: count($suggestions),
                basedOnInsightType: $basedOnInsightType,
                confidenceLevel: $confidenceLevel,
            ),
        );

        return new AdTargetingSuggestionsOutput(
            suggestions: $suggestions,
            suggestionCount: count($suggestions),
            basedOnInsightType: $basedOnInsightType,
            confidenceLevel: $confidenceLevel,
        );
    }
}
