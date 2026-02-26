<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

use App\Domain\SocialListening\Entities\ListeningReport;

final readonly class ListeningReportOutput
{
    /**
     * @param  array<string>  $queryIds
     * @param  array<string, int>  $sentimentBreakdown
     * @param  array<array<string, mixed>>  $topAuthors
     * @param  array<array<string, mixed>>  $topKeywords
     * @param  array<array<string, mixed>>  $platformBreakdown
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public array $queryIds,
        public string $periodFrom,
        public string $periodTo,
        public int $totalMentions,
        public array $sentimentBreakdown,
        public array $topAuthors,
        public array $topKeywords,
        public array $platformBreakdown,
        public string $status,
        public ?string $filePath,
        public ?string $generatedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(ListeningReport $report): self
    {
        return new self(
            id: (string) $report->id,
            organizationId: (string) $report->organizationId,
            queryIds: $report->queryIds,
            periodFrom: $report->periodFrom->format('c'),
            periodTo: $report->periodTo->format('c'),
            totalMentions: $report->totalMentions,
            sentimentBreakdown: $report->sentimentBreakdown->toArray(),
            topAuthors: $report->topAuthors,
            topKeywords: $report->topKeywords,
            platformBreakdown: $report->platformBreakdown,
            status: $report->status->value,
            filePath: $report->filePath,
            generatedAt: $report->generatedAt?->format('c'),
            createdAt: $report->createdAt->format('c'),
        );
    }
}
