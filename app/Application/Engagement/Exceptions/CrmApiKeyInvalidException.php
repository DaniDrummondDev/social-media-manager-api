<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CrmApiKeyInvalidException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct(
            message: 'API key CRM inválida ou não autorizada.',
            errorCode: 'CRM_API_KEY_INVALID',
        );
    }
}
