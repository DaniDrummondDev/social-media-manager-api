<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ReportExportRequested extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $type,
        public string $format,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'analytics.report_export_requested';
    }
}
