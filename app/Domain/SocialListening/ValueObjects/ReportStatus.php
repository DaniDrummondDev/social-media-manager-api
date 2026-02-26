<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\ValueObjects;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
