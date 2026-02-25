<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class GetNetworkAnalyticsInput
{
    public function __construct(
        public string $organizationId,
        public string $provider,
        public string $period,
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
