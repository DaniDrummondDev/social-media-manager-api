<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class WeakPasswordException extends DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct(
            message: "Password does not meet security requirements: {$reason}",
            errorCode: 'WEAK_PASSWORD',
        );
    }
}
