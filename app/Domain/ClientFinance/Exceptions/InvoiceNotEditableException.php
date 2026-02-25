<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvoiceNotEditableException extends DomainException
{
    public function __construct(string $message = 'Fatura não pode ser editada neste status.')
    {
        parent::__construct($message, 'INVOICE_NOT_EDITABLE');
    }
}
