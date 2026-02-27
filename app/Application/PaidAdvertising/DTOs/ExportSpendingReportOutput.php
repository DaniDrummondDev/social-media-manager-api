<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class ExportSpendingReportOutput
{
    public function __construct(
        public string $exportId,
        public string $status,
    ) {}
}
