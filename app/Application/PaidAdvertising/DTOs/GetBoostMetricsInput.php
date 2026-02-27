<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class GetBoostMetricsInput
{
    public function __construct(
        public string $organizationId,
        public string $boostId,
        public ?string $period = null,
    ) {}
}
