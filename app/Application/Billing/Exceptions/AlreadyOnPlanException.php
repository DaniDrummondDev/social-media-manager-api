<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AlreadyOnPlanException extends ApplicationException
{
    public function __construct(string $message = 'A organização já está neste plano.')
    {
        parent::__construct($message, 'ALREADY_ON_PLAN');
    }
}
