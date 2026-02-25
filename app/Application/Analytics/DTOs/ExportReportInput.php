<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

final readonly class ExportReportInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $type,
        public string $format,
        public ?string $period = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $filterProvider = null,
        public ?string $filterCampaignId = null,
        public ?string $filterContentId = null,
    ) {}
}
