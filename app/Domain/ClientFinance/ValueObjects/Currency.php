<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\ValueObjects;

enum Currency: string
{
    case BRL = 'BRL';
    case USD = 'USD';
    case EUR = 'EUR';
}
