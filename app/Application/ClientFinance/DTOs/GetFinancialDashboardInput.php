<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class GetFinancialDashboardInput
{
    public function __construct(
        public string $organizationId,
    ) {}
}
