<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidInvoiceTransitionException extends DomainException
{
    public function __construct(string $message = 'Transição de status inválida para a fatura.')
    {
        parent::__construct($message, 'INVALID_INVOICE_TRANSITION');
    }
}
