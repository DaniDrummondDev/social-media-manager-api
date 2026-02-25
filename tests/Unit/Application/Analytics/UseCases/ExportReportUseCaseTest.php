<?php

declare(strict_types=1);

use App\Application\Analytics\DTOs\ExportReportInput;
use App\Application\Analytics\Exceptions\ExportRateLimitExceededException;
use App\Application\Analytics\UseCases\ExportReportUseCase;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;

it('creates export with Processing status', function () {
    $repo = Mockery::mock(ReportExportRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('countRecentByUser')->once()->andReturn(0);
    $repo->shouldReceive('create')->once();
    $dispatcher->shouldReceive('dispatch')->once();

    $useCase = new ExportReportUseCase($repo, $dispatcher);

    $output = $useCase->execute(new ExportReportInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        userId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        type: 'overview',
        format: 'pdf',
        period: '30d',
    ));

    expect($output->status)->toBe('processing')
        ->and($output->type)->toBe('overview')
        ->and($output->format)->toBe('pdf');
});

it('throws when rate limit exceeded', function () {
    $repo = Mockery::mock(ReportExportRepositoryInterface::class);
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);

    $repo->shouldReceive('countRecentByUser')->once()->andReturn(5);

    $useCase = new ExportReportUseCase($repo, $dispatcher);

    $useCase->execute(new ExportReportInput(
        organizationId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        userId: (string) \App\Domain\Shared\ValueObjects\Uuid::generate(),
        type: 'overview',
        format: 'csv',
    ));
})->throws(ExportRateLimitExceededException::class);
