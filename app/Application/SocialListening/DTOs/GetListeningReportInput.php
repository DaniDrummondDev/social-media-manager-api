<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class GetListeningReportInput
{
    public function __construct(
        public string $organizationId,
        public string $reportId,
    ) {}
}
