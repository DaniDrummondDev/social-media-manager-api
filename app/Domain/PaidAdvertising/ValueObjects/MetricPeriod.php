<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

enum MetricPeriod: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Lifetime = 'lifetime';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Por Hora',
            self::Daily => 'Diario',
            self::Weekly => 'Semanal',
            self::Lifetime => 'Acumulado',
        };
    }
}
