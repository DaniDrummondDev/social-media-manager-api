<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class GetAdAnalyticsOverviewInput
{
    public function __construct(
        public string $organizationId,
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
