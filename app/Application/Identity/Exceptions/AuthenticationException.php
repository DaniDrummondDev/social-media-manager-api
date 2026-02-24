<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AuthenticationException extends ApplicationException
{
    public function __construct(string $message = 'Invalid credentials')
    {
        parent::__construct($message, 'AUTHENTICATION_ERROR');
    }
}
