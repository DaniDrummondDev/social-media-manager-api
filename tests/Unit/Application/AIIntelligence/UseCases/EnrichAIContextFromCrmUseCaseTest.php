<?php

declare(strict_types=1);

use App\Application\AIIntelligence\UseCases\EnrichAIContextFromCrmUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\CrmIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Events\CrmAIContextEnriched;
use App\Domain\AIIntelligence\Repositories\CrmConversionAttributionRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

it('enriches context and dispatches CrmAIContextEnriched event', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $attrRepo = Mockery::mock(CrmConversionAttributionRepositoryInterface::class);
    $attrRepo->shouldReceive('countByOrganizationAndType')->times(3)->andReturn(5, 3, 2);

    $provider = Mockery::mock(CrmIntelligenceProviderInterface::class);
    $provider->shouldReceive('getConversionSummary')->once()->andReturn(['total' => 10]);
    $provider->shouldReceive('getAudienceSegments')->once()->andReturn([
        ['segment' => 'high_engagement'],
        ['segment' => 'new_followers'],
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (CrmAIContextEnriched $event) use ($orgId) {
            return $event->organizationId === $orgId
                && $event->conversionCount === 10
                && $event->segmentsCount === 2
                && in_array('crm_conversion_data', $event->contextTypesUpdated)
                && in_array('crm_audience_segments', $event->contextTypesUpdated);
        });

    $useCase = new EnrichAIContextFromCrmUseCase($attrRepo, $provider, $dispatcher);
    $useCase->execute($orgId, $userId);
});

it('dispatches event with empty context types when no data', function () {
    $orgId = (string) Uuid::generate();
    $userId = (string) Uuid::generate();

    $attrRepo = Mockery::mock(CrmConversionAttributionRepositoryInterface::class);
    $attrRepo->shouldReceive('countByOrganizationAndType')->times(3)->andReturn(0, 0, 0);

    $provider = Mockery::mock(CrmIntelligenceProviderInterface::class);
    $provider->shouldReceive('getConversionSummary')->once()->andReturn([]);
    $provider->shouldReceive('getAudienceSegments')->once()->andReturn([]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (CrmAIContextEnriched $event) {
            return $event->conversionCount === 0
                && $event->segmentsCount === 0
                && $event->contextTypesUpdated === [];
        });

    $useCase = new EnrichAIContextFromCrmUseCase($attrRepo, $provider, $dispatcher);
    $useCase->execute($orgId, $userId);
});
