<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class UploadSmallMediaInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $originalName,
        public string $mimeType,
        public int $fileSize,
        public string $contents,
        public string $checksum,
    ) {}
}
