<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class PlanLimitExceededException extends ApplicationException
{
    public function __construct(string $resourceType, int $limit)
    {
        parent::__construct(
            "O limite do plano para '{$resourceType}' foi atingido ({$limit}). Faça upgrade para continuar.",
            'PLAN_LIMIT_REACHED',
        );
    }
}
