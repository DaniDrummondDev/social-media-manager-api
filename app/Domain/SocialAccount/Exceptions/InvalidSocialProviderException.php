<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidSocialProviderException extends DomainException
{
    public function __construct(string $provider)
    {
        parent::__construct(
            message: "Provider '{$provider}' is not supported.",
            errorCode: 'INVALID_SOCIAL_PROVIDER',
        );
    }
}
