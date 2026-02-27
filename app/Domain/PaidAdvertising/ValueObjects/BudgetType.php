<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum BudgetType: string
{
    case Daily = 'daily';
    case Lifetime = 'lifetime';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Diario',
            self::Lifetime => 'Vitalicio',
        };
    }
}
