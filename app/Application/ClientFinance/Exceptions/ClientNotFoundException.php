<?php

declare(strict_types=1);

namespace App\Application\ClientFinance\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ClientNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Cliente não encontrado.')
    {
        parent::__construct($message, 'CLIENT_NOT_FOUND');
    }
}
