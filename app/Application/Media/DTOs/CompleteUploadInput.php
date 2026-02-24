<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class CompleteUploadInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $uploadId,
        public string $checksum,
    ) {}
}
