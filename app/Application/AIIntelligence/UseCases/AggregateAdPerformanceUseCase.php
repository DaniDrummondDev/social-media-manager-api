<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\UseCases;

use App\Application\AIIntelligence\DTOs\AdPerformanceInsightOutput;
use App\Application\AIIntelligence\DTOs\AggregateAdPerformanceInput;
use App\Application\AIIntelligence\Exceptions\InsufficientAdDataException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\AdIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class AggregateAdPerformanceUseCase
{
    public function __construct(
        private readonly AdPerformanceInsightRepositoryInterface $insightRepository,
        private readonly AdIntelligenceProviderInterface $adIntelligenceProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(AggregateAdPerformanceInput $input): AdPerformanceInsightOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $adInsightType = AdInsightType::from($input->adInsightType);

        $data = match ($adInsightType) {
            AdInsightType::BestAudiences => $this->adIntelligenceProvider->getBestAudiences($organizationId),
            AdInsightType::BestContentForAds => $this->adIntelligenceProvider->getBestContentForAds($organizationId),
            AdInsightType::OrganicVsPaidCorrelation => $this->adIntelligenceProvider->getOrganicVsPaidCorrelation($organizationId),
        };

        $sampleSize = (int) ($data['sample_size'] ?? 0);

        if (! AdPerformanceInsight::hasEnoughData($sampleSize)) {
            throw new InsufficientAdDataException($sampleSize, AdPerformanceInsight::minBoostsRequired());
        }

        $now = new DateTimeImmutable;
        $periodStart = new DateTimeImmutable($data['period_start'] ?? '-7 days');
        $periodEnd = new DateTimeImmutable($data['period_end'] ?? 'now');

        $existing = $this->insightRepository->findByOrganizationAndType($organizationId, $adInsightType);

        if ($existing !== null) {
            $insight = $existing->refresh(
                insightData: $data,
                sampleSize: $sampleSize,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                userId: $input->userId,
            );
        } else {
            $insight = AdPerformanceInsight::create(
                organizationId: $organizationId,
                adInsightType: $adInsightType,
                insightData: $data,
                sampleSize: $sampleSize,
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                userId: $input->userId,
            );
        }

        $this->insightRepository->save($insight);
        $this->eventDispatcher->dispatch(...$insight->domainEvents);

        return AdPerformanceInsightOutput::fromEntity($insight);
    }
}
