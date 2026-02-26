<?php

declare(strict_types=1);

namespace App\Application\SocialListening\UseCases;

use App\Application\SocialListening\DTOs\GenerateListeningReportInput;
use App\Application\SocialListening\DTOs\ListeningReportOutput;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Entities\ListeningReport;
use App\Domain\SocialListening\Repositories\ListeningReportRepositoryInterface;
use DateTimeImmutable;

final class GenerateListeningReportUseCase
{
    public function __construct(
        private readonly ListeningReportRepositoryInterface $reportRepository,
    ) {}

    public function execute(GenerateListeningReportInput $input): ListeningReportOutput
    {
        $organizationId = Uuid::fromString($input->organizationId);

        $report = ListeningReport::create(
            organizationId: $organizationId,
            queryIds: $input->queryIds,
            periodFrom: new DateTimeImmutable($input->periodFrom),
            periodTo: new DateTimeImmutable($input->periodTo),
            userId: $input->userId,
        );

        $this->reportRepository->create($report);

        return ListeningReportOutput::fromEntity($report);
    }
}
