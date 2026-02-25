<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class GetOverviewInput
{
    public function __construct(
        public string $organizationId,
        public string $period,
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
