<?php

declare(strict_types=1);

namespace App\Infrastructure\Analytics\Services;

use App\Application\Analytics\Contracts\ReportGeneratorInterface;
use App\Domain\Analytics\Entities\ReportExport;
use RuntimeException;

final class StubReportGenerator implements ReportGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{path: string, size: int}
     */
    public function generate(ReportExport $export, array $data): array
    {
        throw new RuntimeException('StubReportGenerator::generate() is not implemented yet.');
    }
}
