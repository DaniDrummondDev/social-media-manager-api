<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class PlanNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Plano não encontrado.')
    {
        parent::__construct($message, 'PLAN_NOT_FOUND');
    }
}
