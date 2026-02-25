<?php

declare(strict_types=1);

namespace App\Domain\ClientFinance\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ClientAlreadyArchivedException extends DomainException
{
    public function __construct(string $message = 'Cliente já está arquivado.')
    {
        parent::__construct($message, 'CLIENT_ALREADY_ARCHIVED');
    }
}
