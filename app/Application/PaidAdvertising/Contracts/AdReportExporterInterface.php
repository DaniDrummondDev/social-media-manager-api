<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Contracts;

interface AdReportExporterInterface
{
    /**
     * Requests an asynchronous spending report export.
     *
     * @return string The export ID for status tracking.
     */
    public function requestExport(
        string $organizationId,
        string $from,
        string $to,
        string $format,
    ): string;
}
