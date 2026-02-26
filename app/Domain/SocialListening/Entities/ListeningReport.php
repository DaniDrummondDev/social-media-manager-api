<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Entities;

use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialListening\Events\ListeningReportGenerated;
use App\Domain\SocialListening\ValueObjects\ReportStatus;
use App\Domain\SocialListening\ValueObjects\SentimentBreakdown;
use DateTimeImmutable;

final readonly class ListeningReport
{
    /**
     * @param  array<string>  $queryIds
     * @param  array<array<string, mixed>>  $topAuthors
     * @param  array<array<string, mixed>>  $topKeywords
     * @param  array<array<string, mixed>>  $platformBreakdown
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public array $queryIds,
        public DateTimeImmutable $periodFrom,
        public DateTimeImmutable $periodTo,
        public int $totalMentions,
        public SentimentBreakdown $sentimentBreakdown,
        public array $topAuthors,
        public array $topKeywords,
        public array $platformBreakdown,
        public ReportStatus $status,
        public ?string $filePath,
        public ?DateTimeImmutable $generatedAt,
        public DateTimeImmutable $createdAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string>  $queryIds
     */
    public static function create(
        Uuid $organizationId,
        array $queryIds,
        DateTimeImmutable $periodFrom,
        DateTimeImmutable $periodTo,
        string $userId,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            queryIds: $queryIds,
            periodFrom: $periodFrom,
            periodTo: $periodTo,
            totalMentions: 0,
            sentimentBreakdown: SentimentBreakdown::empty(),
            topAuthors: [],
            topKeywords: [],
            platformBreakdown: [],
            status: ReportStatus::Pending,
            filePath: null,
            generatedAt: null,
            createdAt: $now,
        );
    }

    /**
     * @param  array<string>  $queryIds
     * @param  array<array<string, mixed>>  $topAuthors
     * @param  array<array<string, mixed>>  $topKeywords
     * @param  array<array<string, mixed>>  $platformBreakdown
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        array $queryIds,
        DateTimeImmutable $periodFrom,
        DateTimeImmutable $periodTo,
        int $totalMentions,
        SentimentBreakdown $sentimentBreakdown,
        array $topAuthors,
        array $topKeywords,
        array $platformBreakdown,
        ReportStatus $status,
        ?string $filePath,
        ?DateTimeImmutable $generatedAt,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            queryIds: $queryIds,
            periodFrom: $periodFrom,
            periodTo: $periodTo,
            totalMentions: $totalMentions,
            sentimentBreakdown: $sentimentBreakdown,
            topAuthors: $topAuthors,
            topKeywords: $topKeywords,
            platformBreakdown: $platformBreakdown,
            status: $status,
            filePath: $filePath,
            generatedAt: $generatedAt,
            createdAt: $createdAt,
        );
    }

    /**
     * @param  array<array<string, mixed>>  $topAuthors
     * @param  array<array<string, mixed>>  $topKeywords
     * @param  array<array<string, mixed>>  $platformBreakdown
     */
    public function markCompleted(
        int $totalMentions,
        SentimentBreakdown $sentimentBreakdown,
        array $topAuthors,
        array $topKeywords,
        array $platformBreakdown,
        string $filePath,
        string $userId,
    ): self {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            queryIds: $this->queryIds,
            periodFrom: $this->periodFrom,
            periodTo: $this->periodTo,
            totalMentions: $totalMentions,
            sentimentBreakdown: $sentimentBreakdown,
            topAuthors: $topAuthors,
            topKeywords: $topKeywords,
            platformBreakdown: $platformBreakdown,
            status: ReportStatus::Completed,
            filePath: $filePath,
            generatedAt: $now,
            createdAt: $this->createdAt,
            domainEvents: [
                new ListeningReportGenerated(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: $userId,
                ),
            ],
        );
    }

    public function markFailed(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            queryIds: $this->queryIds,
            periodFrom: $this->periodFrom,
            periodTo: $this->periodTo,
            totalMentions: $this->totalMentions,
            sentimentBreakdown: $this->sentimentBreakdown,
            topAuthors: $this->topAuthors,
            topKeywords: $this->topKeywords,
            platformBreakdown: $this->platformBreakdown,
            status: ReportStatus::Failed,
            filePath: $this->filePath,
            generatedAt: $this->generatedAt,
            createdAt: $this->createdAt,
        );
    }
}
