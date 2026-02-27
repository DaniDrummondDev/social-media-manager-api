<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Services;

use App\Application\PaidAdvertising\Contracts\AdReportExporterInterface;

final class StubAdReportExporter implements AdReportExporterInterface
{
    public function requestExport(string $organizationId, string $from, string $to, string $format): string
    {
        return 'export_'.bin2hex(random_bytes(16));
    }
}
