<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Sent, self::Cancelled], true),
            self::Sent => in_array($target, [self::Paid, self::Overdue, self::Cancelled], true),
            self::Overdue => in_array($target, [self::Paid, self::Cancelled], true),
            self::Paid, self::Cancelled => false,
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}
