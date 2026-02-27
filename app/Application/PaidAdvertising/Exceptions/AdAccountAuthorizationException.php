<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AdAccountAuthorizationException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('You do not have access to this ad account.', 'AD_ACCOUNT_AUTHORIZATION_ERROR');
    }
}
