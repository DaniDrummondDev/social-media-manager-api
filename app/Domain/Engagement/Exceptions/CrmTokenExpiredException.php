<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CrmTokenExpiredException extends DomainException
{
    public function __construct(string $message = 'Token da conexão CRM expirado.')
    {
        parent::__construct($message, 'CRM_TOKEN_EXPIRED');
    }
}
