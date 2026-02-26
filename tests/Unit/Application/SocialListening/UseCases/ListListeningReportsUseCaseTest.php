<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Application\SocialListening\DTOs\ListListeningReportsInput;
use App\Application\SocialListening\UseCases\ListListeningReportsUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;

beforeEach(function () {
    $this->reportRepository = Mockery::mock(ListeningReportRepositoryInterface::class);

    $this->useCase = new ListListeningReportsUseCase(
        $this->reportRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
});

it('lists listening reports successfully', function () {
    $report = ListeningReport::reconstitute(
        id: Uuid::fromString('a7b8c9d0-e1f2-3456-abcd-789012345678'),
        organizationId: Uuid::fromString($this->orgId),
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        periodFrom: new DateTimeImmutable('2024-01-01T00:00:00+00:00'),
        periodTo: new DateTimeImmutable('2024-01-31T23:59:59+00:00'),
        totalMentions: 250,
        sentimentBreakdown: SentimentBreakdown::create(positive: 150, neutral: 70, negative: 30),
        topAuthors: [['username' => 'johndoe', 'count' => 25]],
        topKeywords: [['keyword' => 'brand', 'count' => 50]],
        platformBreakdown: [['platform' => 'instagram', 'count' => 200]],
        status: ReportStatus::Completed,
        filePath: '/reports/report-123.pdf',
        generatedAt: new DateTimeImmutable('2024-02-01T10:00:00+00:00'),
        createdAt: new DateTimeImmutable('2024-02-01T09:00:00+00:00'),
    );

    $this->reportRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [$report],
            'next_cursor' => null,
        ]);

    $input = new ListListeningReportsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result)->toBeArray()
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0])->toBeInstanceOf(ListeningReportOutput::class)
        ->and($result['items'][0]->totalMentions)->toBe(250)
        ->and($result['items'][0]->status)->toBe('completed')
        ->and($result['next_cursor'])->toBeNull();
});

it('returns empty list when no reports exist', function () {
    $this->reportRepository->shouldReceive('findByOrganizationId')
        ->once()
        ->andReturn([
            'items' => [],
            'next_cursor' => null,
        ]);

    $input = new ListListeningReportsInput(
        organizationId: $this->orgId,
    );

    $result = $this->useCase->execute($input);

    expect($result['items'])->toBeEmpty()
        ->and($result['next_cursor'])->toBeNull();
});
