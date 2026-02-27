<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class SyncAdMetricsInput
{
    public function __construct(
        public string $boostId,
    ) {}
}
