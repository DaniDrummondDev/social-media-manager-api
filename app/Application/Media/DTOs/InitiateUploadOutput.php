<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class InitiateUploadOutput
{
    public function __construct(
        public string $uploadId,
        public string $s3UploadId,
        public int $chunkSizeBytes,
        public int $totalChunks,
        public string $expiresAt,
    ) {}
}
