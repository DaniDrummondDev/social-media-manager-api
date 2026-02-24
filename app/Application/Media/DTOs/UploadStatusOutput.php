<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

use App\Domain\Media\Entities\MediaUpload;

final readonly class UploadStatusOutput
{
    /**
     * @param  int[]  $receivedChunks
     */
    public function __construct(
        public string $uploadId,
        public string $status,
        public int $totalChunks,
        public array $receivedChunks,
        public float $progress,
        public string $expiresAt,
    ) {}

    public static function fromEntity(MediaUpload $upload): self
    {
        return new self(
            uploadId: (string) $upload->id,
            status: $upload->status->value,
            totalChunks: $upload->totalChunks,
            receivedChunks: $upload->receivedChunks,
            progress: $upload->progress(),
            expiresAt: $upload->expiresAt->format('c'),
        );
    }
}
