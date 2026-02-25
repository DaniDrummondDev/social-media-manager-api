<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CannotCancelFreePlanException extends ApplicationException
{
    public function __construct(string $message = 'Não é possível cancelar o plano gratuito.')
    {
        parent::__construct($message, 'CANNOT_CANCEL_FREE_PLAN');
    }
}
