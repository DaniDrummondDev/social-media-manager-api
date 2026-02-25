<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class UserAlreadyBannedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            'O usuário já está banido.',
            'USER_ALREADY_BANNED',
        );
    }
}
