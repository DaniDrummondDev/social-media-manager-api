<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\ReportExport;
use App\Domain\Analytics\ValueObjects\ExportFormat;
use App\Domain\Analytics\ValueObjects\ExportStatus;
use App\Domain\Analytics\ValueObjects\ReportType;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Analytics\Repositories\EloquentReportExportRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->userId = (string) Str::uuid();
    $this->orgId = (string) Str::uuid();

    DB::table('users')->insert([
        'id' => $this->userId,
        'name' => 'Test',
        'email' => 'test-'.Str::random(6).'@example.com',
        'password' => 'hashed',
        'timezone' => 'UTC',
        'email_verified_at' => now()->toDateTimeString(),
        'two_factor_enabled' => false,
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('organizations')->insert([
        'id' => $this->orgId,
        'name' => 'Test Org',
        'slug' => 'test-'.Str::random(4),
        'timezone' => 'UTC',
        'status' => 'active',
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);
});

it('creates and finds by id', function () {
    $repo = app(EloquentReportExportRepository::class);

    $export = ReportExport::create(
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        type: ReportType::Overview,
        format: ExportFormat::Pdf,
        filters: ['period' => '30d'],
    );

    $repo->create($export);

    $found = $repo->findById($export->id);

    expect($found)->not->toBeNull()
        ->and($found->type)->toBe(ReportType::Overview)
        ->and($found->format)->toBe(ExportFormat::Pdf)
        ->and($found->status)->toBe(ExportStatus::Processing)
        ->and($found->filters)->toBe(['period' => '30d']);
});

it('counts recent exports by user', function () {
    $repo = app(EloquentReportExportRepository::class);

    for ($i = 0; $i < 3; $i++) {
        $export = ReportExport::create(
            organizationId: Uuid::fromString($this->orgId),
            userId: Uuid::fromString($this->userId),
            type: ReportType::Overview,
            format: ExportFormat::Csv,
        );
        $repo->create($export);
    }

    $count = $repo->countRecentByUser(
        Uuid::fromString($this->userId),
        new DateTimeImmutable('-1 hour'),
    );

    expect($count)->toBe(3);
});

it('finds expired exports', function () {
    $repo = app(EloquentReportExportRepository::class);

    $export = ReportExport::create(
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        type: ReportType::Network,
        format: ExportFormat::Pdf,
    );

    $repo->create($export);

    // Mark as ready with expired time
    $ready = $export->markAsReady('/reports/test.pdf', 1024);
    $repo->update($ready);

    // Override expires_at to past
    DB::table('report_exports')
        ->where('id', (string) $export->id)
        ->update(['expires_at' => now()->subHour()->toDateTimeString()]);

    $expired = $repo->findExpired(new DateTimeImmutable);

    expect($expired)->toHaveCount(1)
        ->and((string) $expired[0]->id)->toBe((string) $export->id);
});

it('finds exports by organization', function () {
    $repo = app(EloquentReportExportRepository::class);

    $export1 = ReportExport::create(
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        type: ReportType::Overview,
        format: ExportFormat::Pdf,
    );
    $repo->create($export1);

    $export2 = ReportExport::create(
        organizationId: Uuid::fromString($this->orgId),
        userId: Uuid::fromString($this->userId),
        type: ReportType::Content,
        format: ExportFormat::Csv,
    );
    $repo->create($export2);

    $exports = $repo->findByOrganizationId(Uuid::fromString($this->orgId));

    expect($exports)->toHaveCount(2);
});
