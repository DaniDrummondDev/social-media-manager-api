<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ContractNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Contrato não encontrado.')
    {
        parent::__construct($message, 'CONTRACT_NOT_FOUND');
    }
}
