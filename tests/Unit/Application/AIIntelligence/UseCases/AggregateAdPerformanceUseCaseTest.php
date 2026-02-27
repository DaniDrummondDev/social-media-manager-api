<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AdPerformanceInsightOutput;
use App\Application\AIIntelligence\DTOs\AggregateAdPerformanceInput;
use App\Application\AIIntelligence\Exceptions\InsufficientAdDataException;
use App\Application\AIIntelligence\UseCases\AggregateAdPerformanceUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\AIIntelligence\Contracts\AdIntelligenceProviderInterface;
use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;

function createReconstitutedInsight(Uuid $orgId, AdInsightType $type): AdPerformanceInsight
{
    $now = new DateTimeImmutable;

    return AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: $orgId,
        adInsightType: $type,
        insightData: ['audiences' => [['audience_id' => 'a1', 'avg_ctr' => 1.5]]],
        sampleSize: 25,
        confidenceLevel: ConfidenceLevel::Medium,
        periodStart: $now->modify('-7 days'),
        periodEnd: $now,
        generatedAt: $now,
        expiresAt: $now->modify('+7 days'),
        createdAt: $now,
        updatedAt: $now,
    );
}

it('creates new insight when none exists and dispatches event', function () {
    $orgId = (string) Uuid::generate();

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);
    $insightRepo->shouldReceive('save')->once();

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getBestAudiences')->once()->andReturn([
        'sample_size' => 30,
        'audiences' => [['audience_id' => 'a1', 'avg_ctr' => 2.0]],
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new AggregateAdPerformanceUseCase($insightRepo, $provider, $dispatcher);

    $output = $useCase->execute(new AggregateAdPerformanceInput(
        organizationId: $orgId,
        userId: 'user-1',
        adInsightType: 'best_audiences',
    ));

    expect($output)->toBeInstanceOf(AdPerformanceInsightOutput::class)
        ->and($output->adInsightType)->toBe('best_audiences')
        ->and($output->sampleSize)->toBe(30)
        ->and($output->confidenceLevel)->toBe('medium');
});

it('refreshes existing insight and dispatches event', function () {
    $orgId = Uuid::generate();

    $existing = createReconstitutedInsight($orgId, AdInsightType::BestAudiences);

    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->once()->andReturn($existing);
    $insightRepo->shouldReceive('save')->once();

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getBestAudiences')->once()->andReturn([
        'sample_size' => 60,
        'audiences' => [['audience_id' => 'a2', 'avg_ctr' => 3.0]],
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new AggregateAdPerformanceUseCase($insightRepo, $provider, $dispatcher);

    $output = $useCase->execute(new AggregateAdPerformanceInput(
        organizationId: (string) $orgId,
        userId: 'user-1',
        adInsightType: 'best_audiences',
    ));

    expect($output->sampleSize)->toBe(60)
        ->and($output->confidenceLevel)->toBe('high')
        ->and($output->id)->toBe((string) $existing->id);
});

it('throws InsufficientAdDataException when sample size below minimum', function () {
    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getBestContentForAds')->once()->andReturn([
        'sample_size' => 3,
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $useCase = new AggregateAdPerformanceUseCase($insightRepo, $provider, $dispatcher);

    $useCase->execute(new AggregateAdPerformanceInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        adInsightType: 'best_content_for_ads',
    ));
})->throws(InsufficientAdDataException::class);

it('calls correct provider method for each insight type', function () {
    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->andReturn(null);
    $insightRepo->shouldReceive('save');

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch');

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getOrganicVsPaidCorrelation')->once()->andReturn([
        'sample_size' => 10,
        'correlation' => 0.85,
    ]);

    $useCase = new AggregateAdPerformanceUseCase($insightRepo, $provider, $dispatcher);

    $output = $useCase->execute(new AggregateAdPerformanceInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        adInsightType: 'organic_vs_paid_correlation',
    ));

    expect($output->adInsightType)->toBe('organic_vs_paid_correlation');
});

it('returns output with correct field mapping', function () {
    $insightRepo = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $insightRepo->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);
    $insightRepo->shouldReceive('save')->once();

    $provider = Mockery::mock(AdIntelligenceProviderInterface::class);
    $provider->shouldReceive('getBestAudiences')->once()->andReturn([
        'sample_size' => 25,
        'audiences' => [['audience_id' => 'a1']],
        'period_start' => '-14 days',
        'period_end' => 'now',
    ]);

    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new AggregateAdPerformanceUseCase($insightRepo, $provider, $dispatcher);

    $output = $useCase->execute(new AggregateAdPerformanceInput(
        organizationId: (string) Uuid::generate(),
        userId: 'user-1',
        adInsightType: 'best_audiences',
    ));

    expect($output->id)->toBeString()
        ->and($output->adInsightType)->toBe('best_audiences')
        ->and($output->adInsightLabel)->toBe('Melhores Audiencias')
        ->and($output->sampleSize)->toBe(25)
        ->and($output->confidenceLevel)->toBe('medium')
        ->and($output->periodStart)->toBeString()
        ->and($output->periodEnd)->toBeString()
        ->and($output->generatedAt)->toBeString()
        ->and($output->expiresAt)->toBeString();
});
