<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Expired = 'expired';

    public function isActive(): bool
    {
        return in_array($this, [self::Trialing, self::Active, self::PastDue], true);
    }
}
