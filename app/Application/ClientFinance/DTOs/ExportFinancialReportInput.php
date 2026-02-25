<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\DTOs;

final readonly class ExportFinancialReportInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $format,
        public ?string $from = null,
        public ?string $to = null,
    ) {}
}
