<?php

declare(strict_types=1);

use App\Application\Analytics\DTOs\GetOverviewInput;
use App\Application\Analytics\UseCases\GetOverviewUseCase;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;

it('returns overview with summary and comparison', function () {
    $repo = Mockery::mock(ContentMetricRepositoryInterface::class);

    $repo->shouldReceive('getAggregatedMetrics')
        ->twice()
        ->andReturn(
            ['impressions' => 1000, 'reach' => 500, 'likes' => 100, 'comments' => 20, 'shares' => 10, 'saves' => 5, 'clicks' => 50, 'engagement_rate' => 27.0, 'total_posts' => 5],
            ['impressions' => 800, 'reach' => 400, 'likes' => 80, 'comments' => 15, 'shares' => 8, 'saves' => 4, 'clicks' => 40, 'engagement_rate' => 26.75, 'total_posts' => 4],
        );

    $repo->shouldReceive('getByNetworkSummary')->once()->andReturn([]);
    $repo->shouldReceive('getDailyTrend')->once()->andReturn([]);
    $repo->shouldReceive('findTopByEngagement')->once()->andReturn([]);

    $useCase = new GetOverviewUseCase($repo);

    $output = $useCase->execute(new GetOverviewInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        period: '30d',
    ));

    expect($output->period)->toBe('30d')
        ->and($output->summary['impressions'])->toBe(1000)
        ->and($output->comparison['impressions']['change'])->toBe(25.0);
});

it('returns zero comparison when previous period has no data', function () {
    $repo = Mockery::mock(ContentMetricRepositoryInterface::class);

    $repo->shouldReceive('getAggregatedMetrics')
        ->twice()
        ->andReturn(
            ['impressions' => 500, 'reach' => 200, 'likes' => 50, 'comments' => 10, 'shares' => 5, 'saves' => 2, 'clicks' => 20, 'engagement_rate' => 33.5],
            [],
        );

    $repo->shouldReceive('getByNetworkSummary')->once()->andReturn([]);
    $repo->shouldReceive('getDailyTrend')->once()->andReturn([]);
    $repo->shouldReceive('findTopByEngagement')->once()->andReturn([]);

    $useCase = new GetOverviewUseCase($repo);

    $output = $useCase->execute(new GetOverviewInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        period: '7d',
    ));

    expect($output->comparison['impressions']['change'])->toBe(0.0);
});
