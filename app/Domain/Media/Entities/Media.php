<?php

declare(strict_types=1);

namespace App\Domain\Media\Entities;

use App\Domain\Media\Events\MediaDeleted;
use App\Domain\Media\Events\MediaRestored;
use App\Domain\Media\Events\MediaScanned;
use App\Domain\Media\Events\MediaUploaded;
use App\Domain\Media\Exceptions\MediaNotUsableException;
use App\Domain\Media\ValueObjects\Compatibility;
use App\Domain\Media\ValueObjects\Dimensions;
use App\Domain\Media\ValueObjects\FileSize;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\ScanStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class Media
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $uploadedBy,
        public string $fileName,
        public string $originalName,
        public MimeType $mimeType,
        public FileSize $fileSize,
        public ?Dimensions $dimensions,
        public ?int $durationSeconds,
        public string $storagePath,
        public ?string $thumbnailPath,
        public string $disk,
        public string $checksum,
        public ScanStatus $scanStatus,
        public ?DateTimeImmutable $scannedAt,
        public ?Compatibility $compatibility,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public ?DateTimeImmutable $purgeAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $uploadedBy,
        string $fileName,
        string $originalName,
        MimeType $mimeType,
        FileSize $fileSize,
        string $storagePath,
        string $disk,
        string $checksum,
        ?Dimensions $dimensions = null,
        ?int $durationSeconds = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        $compatibility = Compatibility::calculate($mimeType, $fileSize, $dimensions, $durationSeconds);

        return new self(
            id: $id,
            organizationId: $organizationId,
            uploadedBy: $uploadedBy,
            fileName: $fileName,
            originalName: $originalName,
            mimeType: $mimeType,
            fileSize: $fileSize,
            dimensions: $dimensions,
            durationSeconds: $durationSeconds,
            storagePath: $storagePath,
            thumbnailPath: null,
            disk: $disk,
            checksum: $checksum,
            scanStatus: ScanStatus::Pending,
            scannedAt: null,
            compatibility: $compatibility,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            domainEvents: [
                new MediaUploaded(
                    aggregateId: (string) $id,
                    organizationId: (string) $organizationId,
                    userId: (string) $uploadedBy,
                    fileName: $fileName,
                    mimeType: $mimeType->value,
                    fileSize: $fileSize->bytes,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $uploadedBy,
        string $fileName,
        string $originalName,
        MimeType $mimeType,
        FileSize $fileSize,
        ?Dimensions $dimensions,
        ?int $durationSeconds,
        string $storagePath,
        ?string $thumbnailPath,
        string $disk,
        string $checksum,
        ScanStatus $scanStatus,
        ?DateTimeImmutable $scannedAt,
        ?Compatibility $compatibility,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
        ?DateTimeImmutable $purgeAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            uploadedBy: $uploadedBy,
            fileName: $fileName,
            originalName: $originalName,
            mimeType: $mimeType,
            fileSize: $fileSize,
            dimensions: $dimensions,
            durationSeconds: $durationSeconds,
            storagePath: $storagePath,
            thumbnailPath: $thumbnailPath,
            disk: $disk,
            checksum: $checksum,
            scanStatus: $scanStatus,
            scannedAt: $scannedAt,
            compatibility: $compatibility,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            purgeAt: $purgeAt,
        );
    }

    public function markAsClean(): self
    {
        $this->ensurePending();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: ScanStatus::Clean,
            scannedAt: $now,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: [
                ...$this->domainEvents,
                new MediaScanned(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->uploadedBy,
                    scanResult: 'clean',
                ),
            ],
        );
    }

    public function markAsRejected(): self
    {
        $this->ensurePending();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: ScanStatus::Rejected,
            scannedAt: $now,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: [
                ...$this->domainEvents,
                new MediaScanned(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->uploadedBy,
                    scanResult: 'rejected',
                ),
            ],
        );
    }

    public function softDelete(int $graceDays = 30): self
    {
        $this->ensureNotDeleted();

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: $this->scanStatus,
            scannedAt: $this->scannedAt,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $now,
            purgeAt: $now->modify("+{$graceDays} days"),
            domainEvents: [
                ...$this->domainEvents,
                new MediaDeleted(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->uploadedBy,
                ),
            ],
        );
    }

    public function restore(): self
    {
        if ($this->deletedAt === null) {
            return $this;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: $this->scanStatus,
            scannedAt: $this->scannedAt,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: null,
            purgeAt: null,
            domainEvents: [
                ...$this->domainEvents,
                new MediaRestored(
                    aggregateId: (string) $this->id,
                    organizationId: (string) $this->organizationId,
                    userId: (string) $this->uploadedBy,
                ),
            ],
        );
    }

    public function setCompatibility(Compatibility $compatibility): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: $this->scanStatus,
            scannedAt: $this->scannedAt,
            compatibility: $compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function setThumbnailPath(string $thumbnailPath): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: $this->scanStatus,
            scannedAt: $this->scannedAt,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $now,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
            domainEvents: $this->domainEvents,
        );
    }

    public function isUsable(): bool
    {
        return $this->scanStatus->isUsable() && $this->deletedAt === null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isPurgeable(): bool
    {
        return $this->deletedAt !== null
            && $this->purgeAt !== null
            && new DateTimeImmutable >= $this->purgeAt;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            uploadedBy: $this->uploadedBy,
            fileName: $this->fileName,
            originalName: $this->originalName,
            mimeType: $this->mimeType,
            fileSize: $this->fileSize,
            dimensions: $this->dimensions,
            durationSeconds: $this->durationSeconds,
            storagePath: $this->storagePath,
            thumbnailPath: $this->thumbnailPath,
            disk: $this->disk,
            checksum: $this->checksum,
            scanStatus: $this->scanStatus,
            scannedAt: $this->scannedAt,
            compatibility: $this->compatibility,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
            purgeAt: $this->purgeAt,
        );
    }

    private function ensureNotDeleted(): void
    {
        if ($this->deletedAt !== null) {
            throw new MediaNotUsableException('Media has been deleted.');
        }
    }

    private function ensurePending(): void
    {
        if ($this->scanStatus !== ScanStatus::Pending) {
            throw new MediaNotUsableException('Media scan has already been processed.');
        }
    }
}
