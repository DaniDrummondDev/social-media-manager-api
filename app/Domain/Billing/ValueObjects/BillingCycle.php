<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
