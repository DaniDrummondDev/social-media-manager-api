<?php

declare(strict_types=1);

use App\Application\PaidAdvertising\Contracts\AdReportExporterInterface;
use App\Application\PaidAdvertising\DTOs\ExportSpendingReportInput;
use App\Application\PaidAdvertising\UseCases\ExportSpendingReportUseCase;
use App\Domain\Shared\ValueObjects\Uuid;

beforeEach(function () {
    $this->reportExporter = Mockery::mock(AdReportExporterInterface::class);

    $this->useCase = new ExportSpendingReportUseCase(
        $this->reportExporter,
    );

    $this->orgId = (string) Uuid::generate();
    $this->userId = (string) Uuid::generate();
});

it('requests CSV export and returns exportId with pending status', function () {
    $exportId = (string) Uuid::generate();

    $this->reportExporter->shouldReceive('requestExport')
        ->once()
        ->with($this->orgId, '2026-02-01', '2026-02-28', 'csv')
        ->andReturn($exportId);

    $output = $this->useCase->execute(new ExportSpendingReportInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        from: '2026-02-01',
        to: '2026-02-28',
        format: 'csv',
    ));

    expect($output->exportId)->toBe($exportId)
        ->and($output->status)->toBe('pending');
});

it('requests PDF export and returns exportId with pending status', function () {
    $exportId = (string) Uuid::generate();

    $this->reportExporter->shouldReceive('requestExport')
        ->once()
        ->with($this->orgId, '2026-02-01', '2026-02-28', 'pdf')
        ->andReturn($exportId);

    $output = $this->useCase->execute(new ExportSpendingReportInput(
        organizationId: $this->orgId,
        userId: $this->userId,
        from: '2026-02-01',
        to: '2026-02-28',
        format: 'pdf',
    ));

    expect($output->exportId)->toBe($exportId)
        ->and($output->status)->toBe('pending');
});
