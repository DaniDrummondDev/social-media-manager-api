<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InsufficientBudgetException extends DomainException
{
    public function __construct(string $message = 'Orcamento insuficiente.')
    {
        parent::__construct(
            message: $message,
            errorCode: 'INSUFFICIENT_BUDGET',
        );
    }
}
