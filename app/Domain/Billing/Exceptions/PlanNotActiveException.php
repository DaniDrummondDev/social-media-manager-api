<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PlanNotActiveException extends DomainException
{
    public function __construct(string $slug = '')
    {
        parent::__construct("O plano '{$slug}' não está ativo.", 'PLAN_NOT_ACTIVE');
    }
}
