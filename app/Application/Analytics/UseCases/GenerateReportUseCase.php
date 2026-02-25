<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\Contracts\ReportGeneratorInterface;
use App\Application\Analytics\Exceptions\ExportNotFoundException;
use App\Application\Shared\Contracts\EventDispatcherInterface;
use App\Domain\Analytics\Repositories\ContentMetricRepositoryInterface;
use App\Domain\Analytics\Repositories\ReportExportRepositoryInterface;
use App\Domain\Analytics\ValueObjects\MetricPeriod;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final class GenerateReportUseCase
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $reportExportRepository,
        private readonly ContentMetricRepositoryInterface $contentMetricRepository,
        private readonly ReportGeneratorInterface $reportGenerator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(string $exportId): void
    {
        $id = Uuid::fromString($exportId);
        $export = $this->reportExportRepository->findById($id);

        if ($export === null) {
            throw new ExportNotFoundException($exportId);
        }

        try {
            $period = $this->resolvePeriodFromFilters($export->filters);
            $data = $this->contentMetricRepository->getAggregatedMetrics($export->organizationId, $period);

            $result = $this->reportGenerator->generate($export, $data);

            $export = $export->markAsReady($result['path'], $result['size']);
        } catch (ExportNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $export = $export->markAsFailed($e->getMessage());
        }

        $this->reportExportRepository->update($export);
        $this->eventDispatcher->dispatch(...$export->domainEvents);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolvePeriodFromFilters(array $filters): MetricPeriod
    {
        $periodType = $filters['period'] ?? '30d';

        if ($periodType === 'custom' && isset($filters['from'], $filters['to'])) {
            return MetricPeriod::custom(
                new DateTimeImmutable($filters['from']),
                new DateTimeImmutable($filters['to']),
            );
        }

        return MetricPeriod::fromPreset((string) $periodType);
    }
}
