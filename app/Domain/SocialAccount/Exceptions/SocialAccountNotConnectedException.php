<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SocialAccountNotConnectedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Social account is not connected.',
            errorCode: 'SOCIAL_ACCOUNT_NOT_CONNECTED',
        );
    }
}
