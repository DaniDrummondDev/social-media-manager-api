<?php

declare(strict_types=1);

namespace App\Domain\Analytics\ValueObjects;

enum ExportFormat: string
{
    case Pdf = 'pdf';
    case Csv = 'csv';
}
