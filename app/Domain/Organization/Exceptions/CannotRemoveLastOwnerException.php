<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CannotRemoveLastOwnerException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Cannot remove the last owner of the organization',
            errorCode: 'CANNOT_REMOVE_LAST_OWNER',
        );
    }
}
