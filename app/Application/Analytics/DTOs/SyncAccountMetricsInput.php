<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class SyncAccountMetricsInput
{
    public function __construct(
        public string $socialAccountId,
    ) {}
}
