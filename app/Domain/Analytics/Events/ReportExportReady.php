<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class ReportExportReady extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        string $organizationId,
        string $userId,
        public string $filePath,
        public int $fileSize,
        public string $readyAt,
    ) {
        parent::__construct($aggregateId, $organizationId, $userId);
    }

    public function eventName(): string
    {
        return 'analytics.report_export_ready';
    }
}
