<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class TokenRefreshFailedException extends DomainException
{
    public function __construct(string $provider, ?string $reason = null)
    {
        $message = "Failed to refresh token for {$provider}.";

        if ($reason !== null) {
            $message .= " Reason: {$reason}";
        }

        parent::__construct(
            message: $message,
            errorCode: 'TOKEN_REFRESH_FAILED',
        );
    }
}
