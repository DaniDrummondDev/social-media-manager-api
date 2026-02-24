<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class SocialAccountAuthorizationException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('You do not have access to this social account.', 'SOCIAL_ACCOUNT_AUTHORIZATION_ERROR');
    }
}
