<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class GetProfitabilityReportInput
{
    public function __construct(
        public string $organizationId,
        public ?string $clientId = null,
        public ?string $referenceMonth = null,
    ) {}
}
