<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\ExportReportInput;
use App\Application\Analytics\DTOs\ExportReportOutput;
use App\Application\Analytics\Exceptions\ExportRateLimitExceededException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Analytics\Entities\ReportExport;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Analytics\ValueObjects\ExportFormat;
use App\Domain\Analytics\ValueObjects\ReportType;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class ExportReportUseCase
{
    private const int MAX_EXPORTS_PER_HOUR = 5;

    public function __construct(
        private readonly ReportExportRepositoryInterface $reportExportRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(ExportReportInput $input): ExportReportOutput
    {
        $userId = Uuid::fromString($input->userId);
        $organizationId = Uuid::fromString($input->organizationId);

        $since = new DateTimeImmutable('-1 hour');
        $recentCount = $this->reportExportRepository->countRecentByUser($userId, $since);

        if ($recentCount >= self::MAX_EXPORTS_PER_HOUR) {
            throw new ExportRateLimitExceededException;
        }

        $filters = array_filter([
            'period' => $input->period,
            'from' => $input->from,
            'to' => $input->to,
            'provider' => $input->filterProvider,
            'campaign_id' => $input->filterCampaignId,
            'content_id' => $input->filterContentId,
        ], fn ($v) => $v !== null);

        $export = ReportExport::create(
            organizationId: $organizationId,
            userId: $userId,
            type: ReportType::from($input->type),
            format: ExportFormat::from($input->format),
            filters: $filters,
        );

        $this->reportExportRepository->create($export);
        $this->eventDispatcher->dispatch(...$export->domainEvents);

        return ExportReportOutput::fromEntity($export);
    }
}
