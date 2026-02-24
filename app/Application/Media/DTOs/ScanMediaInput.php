<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

final readonly class ScanMediaInput
{
    public function __construct(
        public string $mediaId,
        public string $scanResult,
    ) {}
}
