<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\CrmIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Events\CrmAIContextEnriched;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AttributionType;
use App\Domain\Shared\ValueObjects\Uuid;

final class EnrichAIContextFromCrmUseCase
{
    public function __construct(
        private readonly CrmConversionAttributionRepositoryInterface $attributionRepository,
        private readonly CrmIntelligenceProviderInterface $crmIntelligenceProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(string $organizationId, string $userId): void
    {
        $orgId = Uuid::fromString($organizationId);

        $conversionSummary = $this->crmIntelligenceProvider->getConversionSummary($orgId);
        $audienceSegments = $this->crmIntelligenceProvider->getAudienceSegments($orgId);

        $contextTypesUpdated = [];

        if ($conversionSummary !== []) {
            $contextTypesUpdated[] = 'crm_conversion_data';
        }

        if ($audienceSegments !== []) {
            $contextTypesUpdated[] = 'crm_audience_segments';
        }

        $conversionCount = 0;
        foreach (AttributionType::cases() as $type) {
            $conversionCount += $this->attributionRepository->countByOrganizationAndType($orgId, $type);
        }

        $this->eventDispatcher->dispatch(
            new CrmAIContextEnriched(
                aggregateId: $organizationId,
                organizationId: $organizationId,
                userId: $userId,
                contextTypesUpdated: $contextTypesUpdated,
                conversionCount: $conversionCount,
                segmentsCount: count($audienceSegments),
            ),
        );
    }
}
