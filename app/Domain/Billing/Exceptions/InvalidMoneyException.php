<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidMoneyException extends DomainException
{
    public function __construct(string $message = 'Valor monetário inválido.')
    {
        parent::__construct($message, 'INVALID_MONEY');
    }
}
