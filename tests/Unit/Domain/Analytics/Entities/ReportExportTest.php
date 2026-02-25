<?php

declare(strict_types=1);

use App\Domain\Analytics\Entities\ReportExport;
use App\Domain\Analytics\Events\ReportExportReady;
use App\Domain\Analytics\Events\ReportExportRequested;
use App\Domain\Analytics\Exceptions\InvalidExportStatusTransitionException;
use App\Domain\Analytics\ValueObjects\ExportFormat;
use App\Domain\Analytics\ValueObjects\ExportStatus;
use App\Domain\Analytics\ValueObjects\ReportType;
use App\Domain\Shared\ValueObjects\Uuid;

function createProcessingExport(): ReportExport
{
    return ReportExport::create(
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        type: ReportType::Overview,
        format: ExportFormat::Pdf,
        filters: ['period' => '30d'],
    );
}

it('creates with Processing status and emits ReportExportRequested', function () {
    $export = createProcessingExport();

    expect($export->status)->toBe(ExportStatus::Processing)
        ->and($export->type)->toBe(ReportType::Overview)
        ->and($export->format)->toBe(ExportFormat::Pdf)
        ->and($export->filters)->toBe(['period' => '30d'])
        ->and($export->filePath)->toBeNull()
        ->and($export->fileSize)->toBeNull()
        ->and($export->expiresAt)->toBeNull()
        ->and($export->completedAt)->toBeNull()
        ->and($export->domainEvents)->toHaveCount(1)
        ->and($export->domainEvents[0])->toBeInstanceOf(ReportExportRequested::class);
});

it('marks as ready with file path and emits ReportExportReady', function () {
    $export = createProcessingExport();
    $ready = $export->markAsReady('/reports/test.pdf', 1024);

    expect($ready->status)->toBe(ExportStatus::Ready)
        ->and($ready->filePath)->toBe('/reports/test.pdf')
        ->and($ready->fileSize)->toBe(1024)
        ->and($ready->expiresAt)->not->toBeNull()
        ->and($ready->completedAt)->not->toBeNull()
        ->and($ready->errorMessage)->toBeNull()
        ->and($ready->domainEvents)->toHaveCount(2)
        ->and($ready->domainEvents[1])->toBeInstanceOf(ReportExportReady::class);
});

it('marks as failed with error message', function () {
    $export = createProcessingExport();
    $failed = $export->markAsFailed('Generation timeout');

    expect($failed->status)->toBe(ExportStatus::Failed)
        ->and($failed->errorMessage)->toBe('Generation timeout')
        ->and($failed->filePath)->toBeNull()
        ->and($failed->completedAt)->not->toBeNull();
});

it('marks ready export as expired', function () {
    $export = createProcessingExport();
    $ready = $export->markAsReady('/reports/test.pdf', 1024);
    $expired = $ready->markAsExpired();

    expect($expired->status)->toBe(ExportStatus::Expired)
        ->and($expired->filePath)->toBeNull()
        ->and($expired->fileSize)->toBeNull();
});

it('is downloadable when ready with valid file path and not expired', function () {
    $export = createProcessingExport();
    $ready = $export->markAsReady('/reports/test.pdf', 1024);

    expect($ready->isDownloadable())->toBeTrue();
});

it('is not downloadable when failed', function () {
    $export = createProcessingExport();
    $failed = $export->markAsFailed('Error');

    expect($failed->isDownloadable())->toBeFalse();
});

it('is not downloadable when expired', function () {
    $export = createProcessingExport();
    $ready = $export->markAsReady('/reports/test.pdf', 1024);
    $expired = $ready->markAsExpired();

    expect($expired->isDownloadable())->toBeFalse();
});

it('throws on invalid transition from Failed', function () {
    $export = createProcessingExport();
    $failed = $export->markAsFailed('Error');
    $failed->markAsReady('/reports/test.pdf', 1024);
})->throws(InvalidExportStatusTransitionException::class);

it('throws on invalid transition from Expired', function () {
    $export = createProcessingExport();
    $ready = $export->markAsReady('/reports/test.pdf', 1024);
    $expired = $ready->markAsExpired();
    $expired->markAsReady('/reports/test.pdf', 1024);
})->throws(InvalidExportStatusTransitionException::class);

it('releases events', function () {
    $export = createProcessingExport();
    $released = $export->releaseEvents();

    expect($released->domainEvents)->toBeEmpty()
        ->and($released->status)->toBe(ExportStatus::Processing);
});

it('reconstitutes without events', function () {
    $id = Uuid::generate();
    $now = new DateTimeImmutable;

    $export = ReportExport::reconstitute(
        id: $id,
        organizationId: Uuid::generate(),
        userId: Uuid::generate(),
        type: ReportType::Network,
        format: ExportFormat::Csv,
        filters: ['provider' => 'instagram'],
        status: ExportStatus::Ready,
        filePath: '/reports/test.csv',
        fileSize: 2048,
        errorMessage: null,
        expiresAt: $now->modify('+24 hours'),
        completedAt: $now,
        createdAt: $now,
        updatedAt: $now,
    );

    expect($export->id->equals($id))->toBeTrue()
        ->and($export->domainEvents)->toBeEmpty()
        ->and($export->status)->toBe(ExportStatus::Ready);
});
