<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\ExportReportOutput;
use App\Application\Analytics\DTOs\GetExportInput;
use App\Application\Analytics\Exceptions\ExportNotFoundException;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class GetExportUseCase
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $reportExportRepository,
    ) {}

    public function execute(GetExportInput $input): ExportReportOutput
    {
        $exportId = Uuid::fromString($input->exportId);
        $export = $this->reportExportRepository->findById($exportId);

        if ($export === null || (string) $export->organizationId !== $input->organizationId) {
            throw new ExportNotFoundException($input->exportId);
        }

        return ExportReportOutput::fromEntity($export);
    }
}
