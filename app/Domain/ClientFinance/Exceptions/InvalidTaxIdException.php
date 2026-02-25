<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidTaxIdException extends DomainException
{
    public function __construct(string $message = 'Tax ID inválido.')
    {
        parent::__construct($message, 'INVALID_TAX_ID');
    }
}
