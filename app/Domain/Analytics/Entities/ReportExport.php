<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Analytics\Events\ReportExportReady;
use App\Domain\Analytics\Events\ReportExportRequested;
use App\Domain\Analytics\Exceptions\InvalidExportStatusTransitionException;
use App\Domain\Analytics\ValueObjects\ExportFormat;
use App\Domain\Analytics\ValueObjects\ExportStatus;
use App\Domain\Analytics\ValueObjects\ReportType;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class ReportExport
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $userId,
        public ReportType $type,
        public ExportFormat $format,
        public array $filters,
        public ExportStatus $status,
        public ?string $filePath,
        public ?int $fileSize,
        public ?string $errorMessage,
        public ?DateTimeImmutable $expiresAt,
        public ?DateTimeImmutable $completedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function create(
        Uuid $organizationId,
        Uuid $userId,
        ReportType $type,
        ExportFormat $format,
        array $filters = [],
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            type: $type,
            format: $format,
            filters: $filters,
            status: ExportStatus::Processing,
            filePath: null,
            fileSize: null,
            errorMessage: null,
            expiresAt: null,
            completedAt: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new ReportExportRequested(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $userId,
                    type: $type->value,
                    format: $format->value,
                ),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $userId,
        ReportType $type,
        ExportFormat $format,
        array $filters,
        ExportStatus $status,
        ?string $filePath,
        ?int $fileSize,
        ?string $errorMessage,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $completedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            type: $type,
            format: $format,
            filters: $filters,
            status: $status,
            filePath: $filePath,
            fileSize: $fileSize,
            errorMessage: $errorMessage,
            expiresAt: $expiresAt,
            completedAt: $completedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function markAsReady(string $filePath, int $fileSize): self
    {
        $this->assertTransition(ExportStatus::Ready);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            type: $this->type,
            format: $this->format,
            filters: $this->filters,
            status: ExportStatus::Ready,
            filePath: $filePath,
            fileSize: $fileSize,
            errorMessage: null,
            expiresAt: $now->modify('+24 hours'),
            completedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new ReportExportReady(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->userId,
                    filePath: $filePath,
                    fileSize: $fileSize,
                    readyAt: $now->format('c'),
                ),
            ],
        );
    }

    public function markAsFailed(string $errorMessage): self
    {
        $this->assertTransition(ExportStatus::Failed);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            type: $this->type,
            format: $this->format,
            filters: $this->filters,
            status: ExportStatus::Failed,
            filePath: null,
            fileSize: null,
            errorMessage: $errorMessage,
            expiresAt: null,
            completedAt: $now,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function markAsExpired(): self
    {
        $this->assertTransition(ExportStatus::Expired);

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            type: $this->type,
            format: $this->format,
            filters: $this->filters,
            status: ExportStatus::Expired,
            filePath: null,
            fileSize: null,
            errorMessage: null,
            expiresAt: $this->expiresAt,
            completedAt: $this->completedAt,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function isDownloadable(): bool
    {
        return $this->status === ExportStatus::Ready
            && $this->filePath !== null
            && ($this->expiresAt === null || $this->expiresAt > new DateTimeImmutable);
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            type: $this->type,
            format: $this->format,
            filters: $this->filters,
            status: $this->status,
            filePath: $this->filePath,
            fileSize: $this->fileSize,
            errorMessage: $this->errorMessage,
            expiresAt: $this->expiresAt,
            completedAt: $this->completedAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private function assertTransition(ExportStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidExportStatusTransitionException(
                $this->status->value,
                $target->value,
            );
        }
    }
}
