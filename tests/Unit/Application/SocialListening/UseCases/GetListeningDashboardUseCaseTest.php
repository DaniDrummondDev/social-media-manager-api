<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\GetListeningDashboardInput;
use App\Application\SocialListening\DTOs\ListeningDashboardOutput;
use App\Application\SocialListening\UseCases\GetListeningDashboardUseCase;
use App\Domain\SocialListening\Repositories\MentionRepositoryInterface;

beforeEach(function () {
    $this->mentionRepository = Mockery::mock(MentionRepositoryInterface::class);

    $this->useCase = new GetListeningDashboardUseCase(
        $this->mentionRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('gets listening dashboard successfully with default period', function () {
    $this->mentionRepository->shouldReceive('countByOrganizationInPeriod')
        ->once()
        ->andReturn(150);

    $this->mentionRepository->shouldReceive('getSentimentCounts')
        ->once()
        ->andReturn([
            'positive' => 80,
            'neutral' => 50,
            'negative' => 20,
        ]);

    $this->mentionRepository->shouldReceive('getSentimentTrend')
        ->once()
        ->andReturn([
            [
                'date' => '2024-01-15',
                'positive' => 10,
                'neutral' => 5,
                'negative' => 3,
                'total' => 18,
            ],
        ]);

    $this->mentionRepository->shouldReceive('getTopAuthors')
        ->once()
        ->andReturn([
            ['username' => 'johndoe', 'count' => 15],
        ]);

    $this->mentionRepository->shouldReceive('getPlatformBreakdown')
        ->once()
        ->andReturn([
            ['platform' => 'instagram', 'count' => 100],
            ['platform' => 'tiktok', 'count' => 50],
        ]);

    $input = new GetListeningDashboardInput(
        organizationId: $this->orgId,
        period: '7d',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningDashboardOutput::class)
        ->and($output->totalMentions)->toBe(150)
        ->and($output->sentimentBreakdown)->toBe([
            'positive' => 80,
            'neutral' => 50,
            'negative' => 20,
        ])
        ->and($output->mentionsTrend)->toHaveCount(1)
        ->and($output->topAuthors)->toHaveCount(1)
        ->and($output->platformBreakdown)->toHaveCount(2)
        ->and($output->period)->toBe('7d');
});

it('gets listening dashboard with custom date range', function () {
    $this->mentionRepository->shouldReceive('countByOrganizationInPeriod')
        ->once()
        ->andReturn(50);

    $this->mentionRepository->shouldReceive('getSentimentCounts')
        ->once()
        ->andReturn([
            'positive' => 30,
            'neutral' => 15,
            'negative' => 5,
        ]);

    $this->mentionRepository->shouldReceive('getSentimentTrend')
        ->once()
        ->andReturn([]);

    $this->mentionRepository->shouldReceive('getTopAuthors')
        ->once()
        ->andReturn([]);

    $this->mentionRepository->shouldReceive('getPlatformBreakdown')
        ->once()
        ->andReturn([]);

    $input = new GetListeningDashboardInput(
        organizationId: $this->orgId,
        period: '7d',
        from: '2024-01-01T00:00:00+00:00',
        to: '2024-01-31T23:59:59+00:00',
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningDashboardOutput::class)
        ->and($output->totalMentions)->toBe(50);
});
