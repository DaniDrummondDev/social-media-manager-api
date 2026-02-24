<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class EmailAlreadyInUseException extends ApplicationException
{
    public function __construct(string $email)
    {
        parent::__construct("Email {$email} is already in use", 'EMAIL_ALREADY_IN_USE');
    }
}
