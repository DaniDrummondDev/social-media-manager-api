<?php

declare(strict_types=1);

namespace App\Domain\Engagement\ValueObjects;

enum CrmSyncStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Partial = 'partial';

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }
}
