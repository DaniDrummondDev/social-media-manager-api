<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CostAllocationNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Alocação de custo não encontrada.')
    {
        parent::__construct($message, 'COST_ALLOCATION_NOT_FOUND');
    }
}
