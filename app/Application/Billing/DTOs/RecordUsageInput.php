<?php

declare(strict_types=1);

namespace App\Application\Billing\DTOs;

final readonly class RecordUsageInput
{
    public function __construct(
        public string $organizationId,
        public string $resourceType,
        public int $amount = 1,
    ) {}
}
