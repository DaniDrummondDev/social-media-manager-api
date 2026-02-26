<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CrmConnectionNotFoundException extends ApplicationException
{
    public function __construct(string $connectionId)
    {
        parent::__construct(
            message: "Conexão CRM '{$connectionId}' não encontrada.",
            errorCode: 'CRM_CONNECTION_NOT_FOUND',
        );
    }
}
