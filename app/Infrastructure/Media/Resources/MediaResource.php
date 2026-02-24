<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Resources;

use App\Application\Media\DTOs\MediaOutput;

final readonly class MediaResource
{
    /**
     * @param  array<string, bool>|null  $compatibility
     */
    private function __construct(
        private string $id,
        private string $organizationId,
        private string $fileName,
        private string $originalName,
        private string $mimeType,
        private int $fileSize,
        private ?int $width,
        private ?int $height,
        private ?int $durationSeconds,
        private string $storagePath,
        private ?string $thumbnailPath,
        private string $scanStatus,
        private ?array $compatibility,
        private string $createdAt,
    ) {}

    public static function fromOutput(MediaOutput $output): self
    {
        return new self(
            id: $output->id,
            organizationId: $output->organizationId,
            fileName: $output->fileName,
            originalName: $output->originalName,
            mimeType: $output->mimeType,
            fileSize: $output->fileSize,
            width: $output->width,
            height: $output->height,
            durationSeconds: $output->durationSeconds,
            storagePath: $output->storagePath,
            thumbnailPath: $output->thumbnailPath,
            scanStatus: $output->scanStatus,
            compatibility: $output->compatibility,
            createdAt: $output->createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'file_name' => $this->fileName,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'width' => $this->width,
            'height' => $this->height,
            'duration_seconds' => $this->durationSeconds,
            'storage_path' => $this->storagePath,
            'thumbnail_path' => $this->thumbnailPath,
            'scan_status' => $this->scanStatus,
            'compatibility' => $this->compatibility,
            'created_at' => $this->createdAt,
        ];
    }
}
