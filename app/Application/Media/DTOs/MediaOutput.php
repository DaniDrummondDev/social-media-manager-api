<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

use App\Domain\Media\Entities\Media;

final readonly class MediaOutput
{
    /**
     * @param  array<string, bool>|null  $compatibility
     */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $fileName,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public ?int $width,
        public ?int $height,
        public ?int $durationSeconds,
        public string $storagePath,
        public ?string $thumbnailPath,
        public string $scanStatus,
        public ?array $compatibility,
        public string $createdAt,
    ) {}

    public static function fromEntity(Media $media): self
    {
        return new self(
            id: (string) $media->id,
            organizationId: (string) $media->organizationId,
            fileName: $media->fileName,
            originalName: $media->originalName,
            mimeType: $media->mimeType->value,
            fileSize: $media->fileSize->bytes,
            width: $media->dimensions?->width,
            height: $media->dimensions?->height,
            durationSeconds: $media->durationSeconds,
            storagePath: $media->storagePath,
            thumbnailPath: $media->thumbnailPath,
            scanStatus: $media->scanStatus->value,
            compatibility: $media->compatibility?->toArray(),
            createdAt: $media->createdAt->format('c'),
        );
    }
}
