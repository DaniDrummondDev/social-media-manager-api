<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\UseCases;

use App\Application\PaidAdvertising\Contracts\AdReportExporterInterface;
use App\Application\PaidAdvertising\DTOs\ExportSpendingReportInput;
use App\Application\PaidAdvertising\DTOs\ExportSpendingReportOutput;

final class ExportSpendingReportUseCase
{
    public function __construct(
        private readonly AdReportExporterInterface $reportExporter,
    ) {}

    public function execute(ExportSpendingReportInput $input): ExportSpendingReportOutput
    {
        $exportId = $this->reportExporter->requestExport(
            $input->organizationId,
            $input->from,
            $input->to,
            $input->format,
        );

        return new ExportSpendingReportOutput(
            exportId: $exportId,
            status: 'pending',
        );
    }
}
