<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidYearMonthException extends DomainException
{
    public function __construct(string $message = 'Formato de ano/mês inválido.')
    {
        parent::__construct($message, 'INVALID_YEAR_MONTH');
    }
}
