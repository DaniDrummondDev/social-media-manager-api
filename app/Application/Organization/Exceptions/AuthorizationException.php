<?php

declare(strict_types=1);

namespace App\Application\Organization\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AuthorizationException extends ApplicationException
{
    public function __construct(string $message = 'Insufficient permissions')
    {
        parent::__construct($message, 'AUTHORIZATION_ERROR');
    }
}
