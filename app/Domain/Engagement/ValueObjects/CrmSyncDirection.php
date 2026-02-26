<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum CrmSyncDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';
}
