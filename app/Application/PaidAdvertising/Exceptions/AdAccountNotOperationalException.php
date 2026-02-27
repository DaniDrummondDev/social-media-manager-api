<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AdAccountNotOperationalException extends ApplicationException
{
    public function __construct(string $accountId, string $status)
    {
        parent::__construct("Ad account '{$accountId}' is not operational (status: {$status}).", 'AD_ACCOUNT_NOT_OPERATIONAL');
    }
}
