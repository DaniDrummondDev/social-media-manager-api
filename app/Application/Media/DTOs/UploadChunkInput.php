<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class UploadChunkInput
{
    public function __construct(
        public string $organizationId,
        public string $uploadId,
        public int $chunkIndex,
        public string $data,
    ) {}
}
