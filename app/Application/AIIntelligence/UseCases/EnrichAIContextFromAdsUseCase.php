<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Events\AdAIContextEnriched;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class EnrichAIContextFromAdsUseCase
{
    public function __construct(
        private readonly AdPerformanceInsightRepositoryInterface $insightRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(string $organizationId, string $userId): void
    {
        $orgId = Uuid::fromString($organizationId);

        $activeInsights = $this->insightRepository->findActiveByOrganization($orgId);

        $contextTypesUpdated = [];
        $boostDataPoints = 0;

        foreach ($activeInsights as $insight) {
            $contextTypesUpdated[] = $insight->adInsightType->value;
            $boostDataPoints += $insight->sampleSize;
        }

        $this->eventDispatcher->dispatch(
            new AdAIContextEnriched(
                aggregateId: $organizationId,
                organizationId: $organizationId,
                userId: $userId,
                contextTypesUpdated: $contextTypesUpdated,
                insightCount: count($activeInsights),
                boostDataPoints: $boostDataPoints,
            ),
        );
    }
}
