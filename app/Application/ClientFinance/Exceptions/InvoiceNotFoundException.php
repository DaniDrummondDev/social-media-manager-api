<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class InvoiceNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Fatura não encontrada.')
    {
        parent::__construct($message, 'INVOICE_NOT_FOUND');
    }
}
