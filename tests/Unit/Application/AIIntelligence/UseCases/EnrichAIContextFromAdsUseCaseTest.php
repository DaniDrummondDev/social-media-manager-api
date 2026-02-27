<?php

declare(strict_types=1);

use App\Application\AIIntelligence\UseCases\EnrichAIContextFromAdsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Events\AdAIContextEnriched;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;

function createActiveInsight(AdInsightType $type, int $sampleSize = 25): AdPerformanceInsight
{
    $now = new DateTimeImmutable;

    return AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        adInsightType: $type,
        insightData: [],
        sampleSize: $sampleSize,
        confidenceLevel: ConfidenceLevel::fromSampleSize($sampleSize),
        periodStart: $now->modify('-7 days'),
        periodEnd: $now,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
        updatedAt: $now,
    );
}

it('dispatches AdAIContextEnriched event with context types from active insights', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findActiveByOrganization')->once()->andReturn([
        createActiveInsight(AdInsightType::BestAudiences, 30),
        createActiveInsight(AdInsightType::BestContentForAds, 20),
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (AdAIContextEnriched $event) use ($orgId) {
            return $event->organizationId === $orgId
                && $event->insightCount === 2
                && $event->boostDataPoints === 50
                && in_array('best_audiences', $event->contextTypesUpdated)
                && in_array('best_content_for_ads', $event->contextTypesUpdated);
        });

    $useCase = new EnrichAIContextFromAdsUseCase($insightRepo, $dispatcher);
    $useCase->execute($orgId, $userId);
});

it('dispatches event with empty context when no active insights', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findActiveByOrganization')->once()->andReturn([]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (AdAIContextEnriched $event) {
            return $event->insightCount === 0
                && $event->boostDataPoints === 0
                && $event->contextTypesUpdated === [];
        });

    $useCase = new EnrichAIContextFromAdsUseCase($insightRepo, $dispatcher);
    $useCase->execute($orgId, $userId);
});

it('counts boost data points as aggregate sample size', function () {
    $orgId = (string) Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findActiveByOrganization')->once()->andReturn([
        createActiveInsight(AdInsightType::BestAudiences, 15),
        createActiveInsight(AdInsightType::OrganicVsPaidCorrelation, 40),
        createActiveInsight(AdInsightType::BestContentForAds, 55),
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (AdAIContextEnriched $event) {
            return $event->boostDataPoints === 110
                && $event->insightCount === 3;
        });

    $useCase = new EnrichAIContextFromAdsUseCase($insightRepo, $dispatcher);
    $useCase->execute($orgId, (string) Uuid::generate());
});
