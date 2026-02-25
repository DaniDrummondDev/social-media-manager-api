<?php

declare(strict_types=1);

namespace App\Domain\Analytics\ValueObjects;

enum ReportType: string
{
    case Overview = 'overview';
    case Network = 'network';
    case Content = 'content';
}
