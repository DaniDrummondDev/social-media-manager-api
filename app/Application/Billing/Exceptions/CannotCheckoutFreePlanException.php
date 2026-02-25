<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CannotCheckoutFreePlanException extends ApplicationException
{
    public function __construct(string $message = 'Não é possível fazer checkout para o plano gratuito.')
    {
        parent::__construct($message, 'CANNOT_CHECKOUT_FREE_PLAN');
    }
}
