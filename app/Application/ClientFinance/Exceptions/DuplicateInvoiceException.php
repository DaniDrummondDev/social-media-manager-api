<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class DuplicateInvoiceException extends ApplicationException
{
    public function __construct(string $message = 'Já existe uma fatura para este contrato neste mês.')
    {
        parent::__construct($message, 'DUPLICATE_INVOICE');
    }
}
