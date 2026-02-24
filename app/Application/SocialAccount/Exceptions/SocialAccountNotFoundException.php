<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class SocialAccountNotFoundException extends ApplicationException
{
    public function __construct(string $accountId)
    {
        parent::__construct("Social account {$accountId} not found.", 'SOCIAL_ACCOUNT_NOT_FOUND');
    }
}
