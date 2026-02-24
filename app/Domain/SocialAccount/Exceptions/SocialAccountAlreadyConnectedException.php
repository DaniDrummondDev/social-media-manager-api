<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SocialAccountAlreadyConnectedException extends DomainException
{
    public function __construct(string $provider)
    {
        parent::__construct(
            message: "A {$provider} account is already connected to this organization.",
            errorCode: 'SOCIAL_ACCOUNT_ALREADY_CONNECTED',
        );
    }
}
