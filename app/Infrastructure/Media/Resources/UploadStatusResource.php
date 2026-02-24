<?php

declare(strict_types=1);

namespace App\Infrastructure\Media\Resources;

use App\Application\Media\DTOs\UploadStatusOutput;

final readonly class UploadStatusResource
{
    /**
     * @param  int[]  $receivedChunks
     */
    private function __construct(
        private string $uploadId,
        private string $status,
        private int $totalChunks,
        private array $receivedChunks,
        private float $progress,
        private string $expiresAt,
    ) {}

    public static function fromOutput(UploadStatusOutput $output): self
    {
        return new self(
            uploadId: $output->uploadId,
            status: $output->status,
            totalChunks: $output->totalChunks,
            receivedChunks: $output->receivedChunks,
            progress: $output->progress,
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
            'status' => $this->status,
            'total_chunks' => $this->totalChunks,
            'received_chunks' => $this->receivedChunks,
            'progress' => $this->progress,
            'expires_at' => $this->expiresAt,
        ];
    }
}
