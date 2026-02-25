<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidContractTransitionException extends DomainException
{
    public function __construct(string $message = 'Transição de status inválida para o contrato.')
    {
        parent::__construct($message, 'INVALID_CONTRACT_TRANSITION');
    }
}
