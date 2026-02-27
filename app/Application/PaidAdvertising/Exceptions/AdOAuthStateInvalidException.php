<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AdOAuthStateInvalidException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired OAuth state for ad account connection.', 'AD_OAUTH_STATE_INVALID');
    }
}
