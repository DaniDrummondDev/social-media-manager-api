<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class UserAlreadyVerifiedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'User email is already verified',
            errorCode: 'USER_ALREADY_VERIFIED',
        );
    }
}
