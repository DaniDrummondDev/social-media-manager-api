<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class ExportSpendingReportInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $from,
        public string $to,
        public string $format,
    ) {}
}
