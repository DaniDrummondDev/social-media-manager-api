<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Events\ListeningReportGenerated;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;

it('creates with pending status', function () {
    $orgId = Uuid::generate();
    $periodFrom = new DateTimeImmutable('2025-01-01');
    $periodTo = new DateTimeImmutable('2025-01-31');

    $report = ListeningReport::create(
        organizationId: $orgId,
        queryIds: ['query-1', 'query-2'],
        periodFrom: $periodFrom,
        periodTo: $periodTo,
        userId: 'user-1',
    );

    expect($report->status)->toBe(ReportStatus::Pending)
        ->and($report->totalMentions)->toBe(0)
        ->and($report->sentimentBreakdown->total())->toBe(0)
        ->and($report->topAuthors)->toBeEmpty()
        ->and($report->topKeywords)->toBeEmpty()
        ->and($report->platformBreakdown)->toBeEmpty()
        ->and($report->filePath)->toBeNull()
        ->and($report->generatedAt)->toBeNull()
        ->and($report->queryIds)->toBe(['query-1', 'query-2']);
});

it('marks completed with data', function () {
    $report = ListeningReport::create(
        organizationId: Uuid::generate(),
        queryIds: ['query-1'],
        periodFrom: new DateTimeImmutable('2025-01-01'),
        periodTo: new DateTimeImmutable('2025-01-31'),
        userId: 'user-1',
    );

    $sentiment = SentimentBreakdown::create(50, 30, 20);
    $topAuthors = [['username' => 'johndoe', 'mentions' => 15]];
    $topKeywords = [['keyword' => 'brand', 'count' => 42]];
    $platformBreakdown = [['platform' => 'instagram', 'count' => 80]];

    $completed = $report->markCompleted(
        totalMentions: 100,
        sentimentBreakdown: $sentiment,
        topAuthors: $topAuthors,
        topKeywords: $topKeywords,
        platformBreakdown: $platformBreakdown,
        filePath: '/reports/2025-01/report.pdf',
        userId: 'user-1',
    );

    expect($completed->status)->toBe(ReportStatus::Completed)
        ->and($completed->totalMentions)->toBe(100)
        ->and($completed->sentimentBreakdown->positive)->toBe(50)
        ->and($completed->sentimentBreakdown->neutral)->toBe(30)
        ->and($completed->sentimentBreakdown->negative)->toBe(20)
        ->and($completed->topAuthors)->toBe($topAuthors)
        ->and($completed->topKeywords)->toBe($topKeywords)
        ->and($completed->platformBreakdown)->toBe($platformBreakdown)
        ->and($completed->filePath)->toBe('/reports/2025-01/report.pdf')
        ->and($completed->generatedAt)->not->toBeNull()
        ->and($completed->domainEvents)->toHaveCount(1)
        ->and($completed->domainEvents[0])->toBeInstanceOf(ListeningReportGenerated::class);
});

it('marks failed', function () {
    $report = ListeningReport::create(
        organizationId: Uuid::generate(),
        queryIds: ['query-1'],
        periodFrom: new DateTimeImmutable('2025-01-01'),
        periodTo: new DateTimeImmutable('2025-01-31'),
        userId: 'user-1',
    );

    $failed = $report->markFailed();

    expect($failed->status)->toBe(ReportStatus::Failed)
        ->and($failed->totalMentions)->toBe(0)
        ->and($failed->filePath)->toBeNull()
        ->and($report->status)->toBe(ReportStatus::Pending);
});

it('reconstitutes', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $periodFrom = new DateTimeImmutable('2025-01-01');
    $periodTo = new DateTimeImmutable('2025-01-31');
    $generatedAt = new DateTimeImmutable('2025-02-01 10:00:00');
    $createdAt = new DateTimeImmutable('2025-02-01 09:30:00');
    $sentiment = SentimentBreakdown::create(40, 35, 25);
    $topAuthors = [['username' => 'user1', 'mentions' => 10]];
    $topKeywords = [['keyword' => 'tech', 'count' => 30]];
    $platformBreakdown = [['platform' => 'tiktok', 'count' => 60]];

    $report = ListeningReport::reconstitute(
        id: $id,
        organizationId: $orgId,
        queryIds: ['q-1'],
        periodFrom: $periodFrom,
        periodTo: $periodTo,
        totalMentions: 200,
        sentimentBreakdown: $sentiment,
        topAuthors: $topAuthors,
        topKeywords: $topKeywords,
        platformBreakdown: $platformBreakdown,
        status: ReportStatus::Completed,
        filePath: '/reports/report.pdf',
        generatedAt: $generatedAt,
        createdAt: $createdAt,
    );

    expect($report->id)->toEqual($id)
        ->and($report->organizationId)->toEqual($orgId)
        ->and($report->queryIds)->toBe(['q-1'])
        ->and($report->totalMentions)->toBe(200)
        ->and($report->sentimentBreakdown->positive)->toBe(40)
        ->and($report->status)->toBe(ReportStatus::Completed)
        ->and($report->filePath)->toBe('/reports/report.pdf')
        ->and($report->generatedAt)->toEqual($generatedAt)
        ->and($report->domainEvents)->toBeEmpty();
});
