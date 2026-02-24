<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Resources;

use App\Application\Media\DTOs\InitiateUploadOutput;

final readonly class InitiateUploadResource
{
    private function __construct(
        private string $uploadId,
        private string $s3UploadId,
        private int $chunkSizeBytes,
        private int $totalChunks,
        private string $expiresAt,
    ) {}

    public static function fromOutput(InitiateUploadOutput $output): self
    {
        return new self(
            uploadId: $output->uploadId,
            s3UploadId: $output->s3UploadId,
            chunkSizeBytes: $output->chunkSizeBytes,
            totalChunks: $output->totalChunks,
            expiresAt: $output->expiresAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'upload_id' => $this->uploadId,
            's3_upload_id' => $this->s3UploadId,
            'chunk_size_bytes' => $this->chunkSizeBytes,
            'total_chunks' => $this->totalChunks,
            'expires_at' => $this->expiresAt,
        ];
    }
}
