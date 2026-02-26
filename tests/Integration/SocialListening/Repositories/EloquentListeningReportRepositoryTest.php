<?php

declare(strict_types=1);

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->repository = app(ListeningReportRepositoryInterface::class);

    $this->userId = (string) Uuid::generate();
    $this->orgId = (string) Uuid::generate();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test User',
        'email' => 'report-repo-'.Str::random(6).'@example.com',
        'password' => bcrypt('password'),
        'status' => 'active',
        'two_factor_enabled' => false,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'report-repo-'.Str::random(4),
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and retrieves by id', function () {
    $queryId = (string) Uuid::generate();

    $report = ListeningReport::create(
        organizationId: Uuid::fromString($this->orgId),
        queryIds: [$queryId],
        periodFrom: new DateTimeImmutable('2026-02-01'),
        periodTo: new DateTimeImmutable('2026-02-28'),
        userId: $this->userId,
    );

    $this->repository->create($report);

    $found = $this->repository->findById($report->id);

    expect($found)->not->toBeNull()
        ->and((string) $found->id)->toBe((string) $report->id)
        ->and((string) $found->organizationId)->toBe($this->orgId)
        ->and($found->queryIds)->toBe([$queryId])
        ->and($found->totalMentions)->toBe(0)
        ->and($found->status)->toBe(ReportStatus::Pending)
        ->and($found->filePath)->toBeNull()
        ->and($found->generatedAt)->toBeNull()
        ->and($found->sentimentBreakdown->positive)->toBe(0)
        ->and($found->sentimentBreakdown->neutral)->toBe(0)
        ->and($found->sentimentBreakdown->negative)->toBe(0)
        ->and($found->topAuthors)->toBe([])
        ->and($found->topKeywords)->toBe([])
        ->and($found->platformBreakdown)->toBe([]);
});

it('returns null for non-existent id', function () {
    expect($this->repository->findById(Uuid::generate()))->toBeNull();
});

it('finds by organization id with cursor pagination', function () {
    $orgId = Uuid::fromString($this->orgId);

    for ($i = 1; $i <= 5; $i++) {
        $report = ListeningReport::create(
            organizationId: $orgId,
            queryIds: [(string) Uuid::generate()],
            periodFrom: new DateTimeImmutable("2026-0{$i}-01"),
            periodTo: new DateTimeImmutable("2026-0{$i}-28"),
            userId: $this->userId,
        );
        $this->repository->create($report);
    }

    $firstPage = $this->repository->findByOrganizationId($orgId, null, 3);

    expect($firstPage['items'])->toHaveCount(3)
        ->and($firstPage['next_cursor'])->not->toBeNull();

    $secondPage = $this->repository->findByOrganizationId($orgId, $firstPage['next_cursor'], 3);

    expect($secondPage['items'])->toHaveCount(2)
        ->and($secondPage['next_cursor'])->toBeNull();
});

it('updates a report to completed status', function () {
    $orgId = Uuid::fromString($this->orgId);

    $report = ListeningReport::create(
        organizationId: $orgId,
        queryIds: [(string) Uuid::generate()],
        periodFrom: new DateTimeImmutable('2026-02-01'),
        periodTo: new DateTimeImmutable('2026-02-28'),
        userId: $this->userId,
    );
    $this->repository->create($report);

    $sentimentBreakdown = SentimentBreakdown::create(50, 30, 20);
    $topAuthors = [
        ['author_username' => 'top_user', 'count' => 15],
    ];
    $topKeywords = [
        ['keyword' => 'brand', 'count' => 25],
    ];
    $platformBreakdown = [
        ['platform' => 'instagram', 'count' => 60],
        ['platform' => 'tiktok', 'count' => 40],
    ];

    $completed = $report->markCompleted(
        totalMentions: 100,
        sentimentBreakdown: $sentimentBreakdown,
        topAuthors: $topAuthors,
        topKeywords: $topKeywords,
        platformBreakdown: $platformBreakdown,
        filePath: '/reports/2026-02-report.pdf',
        userId: $this->userId,
    );
    $this->repository->update($completed);

    $found = $this->repository->findById($report->id);

    expect($found->status)->toBe(ReportStatus::Completed)
        ->and($found->totalMentions)->toBe(100)
        ->and($found->sentimentBreakdown->positive)->toBe(50)
        ->and($found->sentimentBreakdown->neutral)->toBe(30)
        ->and($found->sentimentBreakdown->negative)->toBe(20)
        ->and($found->topAuthors)->toBe($topAuthors)
        ->and($found->topKeywords)->toBe($topKeywords)
        ->and($found->platformBreakdown)->toBe($platformBreakdown)
        ->and($found->filePath)->toBe('/reports/2026-02-report.pdf')
        ->and($found->generatedAt)->not->toBeNull();
});
