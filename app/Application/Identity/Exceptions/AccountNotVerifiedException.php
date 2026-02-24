<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AccountNotVerifiedException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct('Email not verified', 'ACCOUNT_NOT_VERIFIED');
    }
}
