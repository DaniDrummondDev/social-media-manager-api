<?php

declare(strict_types=1);

use App\Application\SocialListening\DTOs\GetListeningReportInput;
use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Application\SocialListening\Exceptions\ListeningReportNotFoundException;
use App\Application\SocialListening\UseCases\GetListeningReportUseCase;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;

beforeEach(function () {
    $this->reportRepository = Mockery::mock(ListeningReportRepositoryInterface::class);

    $this->useCase = new GetListeningReportUseCase(
        $this->reportRepository,
    );

    $this->orgId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $this->reportId = 'a7b8c9d0-e1f2-3456-abcd-789012345678';
});

it('gets a listening report successfully', function () {
    $report = ListeningReport::reconstitute(
        id: Uuid::fromString($this->reportId),
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

    $this->reportRepository->shouldReceive('findById')->once()->andReturn($report);

    $input = new GetListeningReportInput(
        organizationId: $this->orgId,
        reportId: $this->reportId,
    );

    $output = $this->useCase->execute($input);

    expect($output)->toBeInstanceOf(ListeningReportOutput::class)
        ->and($output->totalMentions)->toBe(250)
        ->and($output->status)->toBe('completed')
        ->and($output->filePath)->toBe('/reports/report-123.pdf');
});

it('throws when report not found', function () {
    $this->reportRepository->shouldReceive('findById')->once()->andReturn(null);

    $input = new GetListeningReportInput(
        organizationId: $this->orgId,
        reportId: $this->reportId,
    );

    $this->useCase->execute($input);
})->throws(ListeningReportNotFoundException::class);

it('throws when report belongs to different organization', function () {
    $differentOrgId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

    $report = ListeningReport::reconstitute(
        id: Uuid::fromString($this->reportId),
        organizationId: Uuid::fromString($differentOrgId),
        queryIds: ['b1c2d3e4-f5a6-7890-bcde-f12345678901'],
        periodFrom: new DateTimeImmutable('2024-01-01'),
        periodTo: new DateTimeImmutable('2024-01-31'),
        totalMentions: 0,
        sentimentBreakdown: SentimentBreakdown::empty(),
        topAuthors: [],
        topKeywords: [],
        platformBreakdown: [],
        status: ReportStatus::Pending,
        filePath: null,
        generatedAt: null,
        createdAt: new DateTimeImmutable('2024-02-01'),
    );

    $this->reportRepository->shouldReceive('findById')->once()->andReturn($report);

    $input = new GetListeningReportInput(
        organizationId: $this->orgId,
        reportId: $this->reportId,
    );

    $this->useCase->execute($input);
})->throws(ListeningReportNotFoundException::class);
