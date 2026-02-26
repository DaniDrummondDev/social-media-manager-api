<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\ValueObjects;

enum SafetyStatus: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Warning = 'warning';
    case Blocked = 'blocked';

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
