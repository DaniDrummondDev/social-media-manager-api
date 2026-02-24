<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class AbortUploadInput
{
    public function __construct(
        public string $organizationId,
        public string $uploadId,
    ) {}
}
