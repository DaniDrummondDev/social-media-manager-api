<?php

declare(strict_types=1);

namespace App\Application\Publishing\DTOs;

final readonly class GetCalendarInput
{
    public function __construct(
        public string $organizationId,
        public ?int $month = null,
        public ?int $year = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $provider = null,
        public ?string $campaignId = null,
    ) {}
}
