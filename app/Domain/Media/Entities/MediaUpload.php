<?php

declare(strict_types=1);

namespace App\Domain\Media\Entities;

use App\Domain\Media\Exceptions\UploadSessionExpiredException;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Media\ValueObjects\UploadStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class MediaUpload
{
    public const int MIN_CHUNK_SIZE = 1 * 1024 * 1024;  // 1MB

    public const int MAX_CHUNK_SIZE = 10 * 1024 * 1024;  // 10MB

    public const int DEFAULT_CHUNK_SIZE = 5 * 1024 * 1024;  // 5MB

    public const int SESSION_TTL_HOURS = 24;

    /**
     * @param  int[]  $receivedChunks
     * @param  array<int, string>  $s3Parts
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public Uuid $organizationId,
        public Uuid $userId,
        public string $fileName,
        public MimeType $mimeType,
        public int $totalBytes,
        public int $chunkSizeBytes,
        public int $totalChunks,
        public array $receivedChunks,
        public ?string $s3UploadId,
        public array $s3Parts,
        public UploadStatus $status,
        public ?string $checksum,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        Uuid $organizationId,
        Uuid $userId,
        string $fileName,
        MimeType $mimeType,
        int $totalBytes,
        int $chunkSizeBytes = self::DEFAULT_CHUNK_SIZE,
    ): self {
        if ($chunkSizeBytes < self::MIN_CHUNK_SIZE || $chunkSizeBytes > self::MAX_CHUNK_SIZE) {
            throw new DomainException(
                message: "Chunk size must be between 1MB and 10MB. Got: {$chunkSizeBytes} bytes.",
                errorCode: 'INVALID_CHUNK_SIZE',
            );
        }

        if ($totalBytes <= 0) {
            throw new DomainException(
                message: 'Total bytes must be greater than zero.',
                errorCode: 'INVALID_TOTAL_BYTES',
            );
        }

        $id = Uuid::generate();
        $now = new DateTimeImmutable;
        $totalChunks = (int) ceil($totalBytes / $chunkSizeBytes);

        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            fileName: $fileName,
            mimeType: $mimeType,
            totalBytes: $totalBytes,
            chunkSizeBytes: $chunkSizeBytes,
            totalChunks: $totalChunks,
            receivedChunks: [],
            s3UploadId: null,
            s3Parts: [],
            status: UploadStatus::Initiated,
            checksum: null,
            expiresAt: $now->modify('+'.self::SESSION_TTL_HOURS.' hours'),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /**
     * @param  int[]  $receivedChunks
     * @param  array<int, string>  $s3Parts
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $organizationId,
        Uuid $userId,
        string $fileName,
        MimeType $mimeType,
        int $totalBytes,
        int $chunkSizeBytes,
        int $totalChunks,
        array $receivedChunks,
        ?string $s3UploadId,
        array $s3Parts,
        UploadStatus $status,
        ?string $checksum,
        DateTimeImmutable $expiresAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            organizationId: $organizationId,
            userId: $userId,
            fileName: $fileName,
            mimeType: $mimeType,
            totalBytes: $totalBytes,
            chunkSizeBytes: $chunkSizeBytes,
            totalChunks: $totalChunks,
            receivedChunks: $receivedChunks,
            s3UploadId: $s3UploadId,
            s3Parts: $s3Parts,
            status: $status,
            checksum: $checksum,
            expiresAt: $expiresAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function setS3UploadId(string $s3UploadId): self
    {
        $this->ensureActive();

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $s3UploadId,
            s3Parts: $this->s3Parts,
            status: $this->status,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function receiveChunk(int $chunkIndex, string $etag): self
    {
        $this->ensureActive();

        if ($chunkIndex < 1 || $chunkIndex > $this->totalChunks) {
            throw new DomainException(
                message: "Invalid chunk index: {$chunkIndex}. Expected 1–{$this->totalChunks}.",
                errorCode: 'INVALID_CHUNK_INDEX',
            );
        }

        if (in_array($chunkIndex, $this->receivedChunks, true)) {
            throw new DomainException(
                message: "Chunk {$chunkIndex} has already been received.",
                errorCode: 'DUPLICATE_CHUNK',
            );
        }

        $receivedChunks = [...$this->receivedChunks, $chunkIndex];
        $s3Parts = $this->s3Parts;
        $s3Parts[$chunkIndex] = $etag;

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $s3Parts,
            status: UploadStatus::Uploading,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function complete(string $checksum): self
    {
        $this->ensureActive();

        if (! $this->allChunksReceived()) {
            $received = count($this->receivedChunks);
            throw new DomainException(
                message: "Cannot complete upload: {$received}/{$this->totalChunks} chunks received.",
                errorCode: 'INCOMPLETE_UPLOAD',
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $this->s3Parts,
            status: UploadStatus::Completing,
            checksum: $checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function markCompleted(): self
    {
        if ($this->status !== UploadStatus::Completing) {
            throw new DomainException(
                message: 'Upload must be in completing state.',
                errorCode: 'INVALID_UPLOAD_STATE',
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $this->s3Parts,
            status: UploadStatus::Completed,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function abort(): self
    {
        if ($this->status->isTerminal()) {
            throw new DomainException(
                message: 'Cannot abort a terminal upload session.',
                errorCode: 'INVALID_UPLOAD_STATE',
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $this->s3Parts,
            status: UploadStatus::Aborted,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function expire(): self
    {
        if ($this->status->isTerminal()) {
            throw new DomainException(
                message: 'Cannot expire a terminal upload session.',
                errorCode: 'INVALID_UPLOAD_STATE',
            );
        }

        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $this->s3Parts,
            status: UploadStatus::Expired,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
            domainEvents: $this->domainEvents,
        );
    }

    public function allChunksReceived(): bool
    {
        return count($this->receivedChunks) === $this->totalChunks;
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable > $this->expiresAt;
    }

    public function progress(): float
    {
        if ($this->totalChunks === 0) {
            return 0.0;
        }

        return round(count($this->receivedChunks) / $this->totalChunks * 100, 2);
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            organizationId: $this->organizationId,
            userId: $this->userId,
            fileName: $this->fileName,
            mimeType: $this->mimeType,
            totalBytes: $this->totalBytes,
            chunkSizeBytes: $this->chunkSizeBytes,
            totalChunks: $this->totalChunks,
            receivedChunks: $this->receivedChunks,
            s3UploadId: $this->s3UploadId,
            s3Parts: $this->s3Parts,
            status: $this->status,
            checksum: $this->checksum,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }

    private function ensureActive(): void
    {
        if ($this->status->isTerminal()) {
            throw new DomainException(
                message: 'Upload session is no longer active.',
                errorCode: 'INVALID_UPLOAD_STATE',
            );
        }

        if ($this->isExpired()) {
            throw new UploadSessionExpiredException;
        }
    }
}
