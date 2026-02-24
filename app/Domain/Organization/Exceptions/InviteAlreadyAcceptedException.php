<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InviteAlreadyAcceptedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Organization invite has already been accepted',
            errorCode: 'INVITE_ALREADY_ACCEPTED',
        );
    }
}
