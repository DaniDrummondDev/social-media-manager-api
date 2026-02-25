<?php

declare(strict_types=1);

namespace App\Application\Analytics\Contracts;

use App\Domain\Analytics\Entities\ReportExport;

interface ReportGeneratorInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{path: string, size: int}
     */
    public function generate(ReportExport $export, array $data): array;
}
