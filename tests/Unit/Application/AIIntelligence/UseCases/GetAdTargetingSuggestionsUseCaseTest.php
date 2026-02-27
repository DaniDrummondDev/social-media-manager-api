<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AdTargetingSuggestionsOutput;
use App\Application\AIIntelligence\DTOs\GetAdTargetingSuggestionsInput;
use App\Application\AIIntelligence\UseCases\GetAdTargetingSuggestionsUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\AdIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Events\AdTargetingSuggested;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;

function createBestAudiencesInsight(Uuid $orgId, ConfidenceLevel $confidence = ConfidenceLevel::Medium): AdPerformanceInsight
{
    $now = new DateTimeImmutable;

    return AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        adInsightType: AdInsightType::BestAudiences,
        insightData: ['audiences' => []],
        sampleSize: 25,
        confidenceLevel: $confidence,
        periodStart: $now->modify('-7 days'),
        periodEnd: $now,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
        updatedAt: $now,
    );
}

it('returns suggestions and dispatches AdTargetingSuggested event', function () {
    $orgId = Uuid::generate();
    $contentId = (string) Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')
        ->once()
        ->andReturn(createBestAudiencesInsight($orgId, ConfidenceLevel::High));

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getTargetingSuggestions')->once()->andReturn([
        'audience_1' => ['name' => 'Young Adults', 'score' => 0.9],
        'audience_2' => ['name' => 'Tech Enthusiasts', 'score' => 0.8],
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')
        ->once()
        ->withArgs(function (AdTargetingSuggested $event) {
            return $event->suggestionCount === 2
                && $event->basedOnInsightType === 'best_audiences'
                && $event->confidenceLevel === 'high';
        });

    $useCase = new GetAdTargetingSuggestionsUseCase($provider, $insightRepo, $dispatcher);

    $output = $useCase->execute(new GetAdTargetingSuggestionsInput(
        organizationId: (string) $orgId,
        userId: 'user-1',
        contentId: $contentId,
    ));

    expect($output)->toBeInstanceOf(AdTargetingSuggestionsOutput::class)
        ->and($output->suggestionCount)->toBe(2)
        ->and($output->basedOnInsightType)->toBe('best_audiences')
        ->and($output->confidenceLevel)->toBe('high');
});

it('returns suggestions with low confidence when no insight exists', function () {
    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getTargetingSuggestions')->once()->andReturn([
        'audience_1' => ['name' => 'General', 'score' => 0.5],
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new GetAdTargetingSuggestionsUseCase($provider, $insightRepo, $dispatcher);

    $output = $useCase->execute(new GetAdTargetingSuggestionsInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        contentId: (string) Uuid::generate(),
    ));

    expect($output->basedOnInsightType)->toBe('none')
        ->and($output->confidenceLevel)->toBe('low');
});

it('uses BestAudiences insight for confidence level', function () {
    $orgId = Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')
        ->once()
        ->withArgs(function (Uuid $id, AdInsightType $type) {
            return $type === AdInsightType::BestAudiences;
        })
        ->andReturn(createBestAudiencesInsight($orgId, ConfidenceLevel::Medium));

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getTargetingSuggestions')->once()->andReturn([]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new GetAdTargetingSuggestionsUseCase($provider, $insightRepo, $dispatcher);

    $output = $useCase->execute(new GetAdTargetingSuggestionsInput(
        organizationId: (string) $orgId,
        userId: 'user-1',
        contentId: (string) Uuid::generate(),
    ));

    expect($output->confidenceLevel)->toBe('medium')
        ->and($output->basedOnInsightType)->toBe('best_audiences');
});

it('returns empty suggestions when provider returns empty', function () {
    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getTargetingSuggestions')->once()->andReturn([]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new GetAdTargetingSuggestionsUseCase($provider, $insightRepo, $dispatcher);

    $output = $useCase->execute(new GetAdTargetingSuggestionsInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        contentId: (string) Uuid::generate(),
    ));

    expect($output->suggestionCount)->toBe(0)
        ->and($output->suggestions)->toBe([]);
});
