<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class OAuthStateInvalidException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('OAuth state token is invalid or expired.', 'OAUTH_STATE_INVALID');
    }
}
