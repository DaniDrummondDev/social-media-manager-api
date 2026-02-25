<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\ExportReportOutput;
use App\Application\Analytics\DTOs\ListExportsInput;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Shared\ValueObjects\Uuid;

final class ListExportsUseCase
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $reportExportRepository,
    ) {}

    /**
     * @return array<ExportReportOutput>
     */
    public function execute(ListExportsInput $input): array
    {
        $organizationId = Uuid::fromString($input->organizationId);
        $exports = $this->reportExportRepository->findByOrganizationId($organizationId);

        return array_map(
            fn ($export) => ExportReportOutput::fromEntity($export),
            $exports,
        );
    }
}
