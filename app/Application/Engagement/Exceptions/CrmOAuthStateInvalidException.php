<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CrmOAuthStateInvalidException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Estado OAuth CRM inválido ou expirado.',
            errorCode: 'CRM_OAUTH_STATE_INVALID',
        );
    }
}
