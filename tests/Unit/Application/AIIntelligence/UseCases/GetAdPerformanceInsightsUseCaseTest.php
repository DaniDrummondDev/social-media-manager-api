<?php

declare(strict_types=1);

use App\Application\AIIntelligence\DTOs\AdPerformanceInsightOutput;
use App\Application\AIIntelligence\DTOs\GetAdPerformanceInsightsInput;
use App\Application\AIIntelligence\UseCases\GetAdPerformanceInsightsUseCase;
use App\Domain\AIIntelligence\Entities\AdPerformanceInsight;
use App\Domain\AIIntelligence\Repositories\AdPerformanceInsightRepositoryInterface;
use App\Domain\AIIntelligence\ValueObjects\AdInsightType;
use App\Domain\AIIntelligence\ValueObjects\ConfidenceLevel;
use App\Domain\Shared\ValueObjects\Uuid;

function createInsightForList(AdInsightType $type, int $sampleSize = 25): AdPerformanceInsight
{
    $now = new DateTimeImmutable;

    return AdPerformanceInsight::reconstitute(
        id: Uuid::generate(),
        organizationId: Uuid::generate(),
        adInsightType: $type,
        insightData: ['data' => 'test'],
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

beforeEach(function () {
    $this->insightRepository = Mockery::mock(AdPerformanceInsightRepositoryInterface::class);
    $this->useCase = new GetAdPerformanceInsightsUseCase($this->insightRepository);
    $this->orgId = (string) Uuid::generate();
});

it('returns all active insights when no type filter', function () {
    $insights = [
        createInsightForList(AdInsightType::BestAudiences),
        createInsightForList(AdInsightType::BestContentForAds),
    ];

    $this->insightRepository->shouldReceive('findActiveByOrganization')->once()->andReturn($insights);

    $result = $this->useCase->execute(new GetAdPerformanceInsightsInput(
        organizationId: $this->orgId,
    ));

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(AdPerformanceInsightOutput::class)
        ->and($result[0]->adInsightType)->toBe('best_audiences')
        ->and($result[1]->adInsightType)->toBe('best_content_for_ads');
});

it('returns filtered insight when type provided', function () {
    $insight = createInsightForList(AdInsightType::OrganicVsPaidCorrelation);

    $this->insightRepository->shouldReceive('findByOrganizationAndType')->once()->andReturn($insight);

    $result = $this->useCase->execute(new GetAdPerformanceInsightsInput(
        organizationId: $this->orgId,
        adInsightType: 'organic_vs_paid_correlation',
    ));

    expect($result)->toHaveCount(1)
        ->and($result[0]->adInsightType)->toBe('organic_vs_paid_correlation');
});

it('returns empty array when type filter has no match', function () {
    $this->insightRepository->shouldReceive('findByOrganizationAndType')->once()->andReturn(null);

    $result = $this->useCase->execute(new GetAdPerformanceInsightsInput(
        organizationId: $this->orgId,
        adInsightType: 'best_audiences',
    ));

    expect($result)->toBe([]);
});

it('returns empty array when no active insights exist', function () {
    $this->insightRepository->shouldReceive('findActiveByOrganization')->once()->andReturn([]);

    $result = $this->useCase->execute(new GetAdPerformanceInsightsInput(
        organizationId: $this->orgId,
    ));

    expect($result)->toBe([]);
});

it('returns output with correct field mapping', function () {
    $insight = createInsightForList(AdInsightType::BestAudiences, 60);

    $this->insightRepository->shouldReceive('findByOrganizationAndType')->once()->andReturn($insight);

    $result = $this->useCase->execute(new GetAdPerformanceInsightsInput(
        organizationId: $this->orgId,
        adInsightType: 'best_audiences',
    ));

    $output = $result[0];

    expect($output->id)->toBe((string) $insight->id)
        ->and($output->adInsightType)->toBe('best_audiences')
        ->and($output->adInsightLabel)->toBe('Melhores Audiencias')
        ->and($output->insightData)->toBe(['data' => 'test'])
        ->and($output->sampleSize)->toBe(60)
        ->and($output->confidenceLevel)->toBe('high')
        ->and($output->periodStart)->toBeString()
        ->and($output->generatedAt)->toBeString()
        ->and($output->expiresAt)->toBeString();
});
