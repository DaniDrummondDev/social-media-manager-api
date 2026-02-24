<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidTwoFactorCodeException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Invalid two-factor authentication code',
            errorCode: 'INVALID_2FA_CODE',
        );
    }
}
