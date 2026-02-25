<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

enum InvoiceStatus: string
{
    case Paid = 'paid';
    case Open = 'open';
    case Void = 'void';
    case Uncollectible = 'uncollectible';
}
