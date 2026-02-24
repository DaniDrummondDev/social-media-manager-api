<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidEmailException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(
            message: "Invalid email format: {$email}",
            errorCode: 'INVALID_EMAIL',
        );
    }
}
