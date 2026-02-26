<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CrmConnectionAlreadyExistsException extends DomainException
{
    public function __construct(string $message = 'Conexão CRM já existe para este provedor nesta organização.')
    {
        parent::__construct($message, 'CRM_CONNECTION_ALREADY_EXISTS');
    }
}
