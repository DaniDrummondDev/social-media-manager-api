<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InviteExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Organization invite has expired',
            errorCode: 'INVITE_EXPIRED',
        );
    }
}
