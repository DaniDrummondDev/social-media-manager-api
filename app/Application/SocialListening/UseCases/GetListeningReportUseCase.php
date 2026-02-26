<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\GetListeningReportInput;
use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Application\SocialListening\Exceptions\ListeningReportNotFoundException;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;

final class GetListeningReportUseCase
{
    public function __construct(
        private readonly ListeningReportRepositoryInterface $reportRepository,
    ) {}

    public function execute(GetListeningReportInput $input): ListeningReportOutput
    {
        $reportId = Uuid::fromString($input->reportId);
        $organizationId = Uuid::fromString($input->organizationId);

        $report = $this->reportRepository->findById($reportId);

        if ($report === null || (string) $report->organizationId !== (string) $organizationId) {
            throw new ListeningReportNotFoundException();
        }

        return ListeningReportOutput::fromEntity($report);
    }
}
