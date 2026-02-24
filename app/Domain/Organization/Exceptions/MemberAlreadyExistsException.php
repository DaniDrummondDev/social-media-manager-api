<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class MemberAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'User is already a member of this organization',
            errorCode: 'MEMBER_ALREADY_EXISTS',
        );
    }
}
