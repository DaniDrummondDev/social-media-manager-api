<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class WebhookLimitExceededException extends ApplicationException
{
    public function __construct(int $limit = 10)
    {
        parent::__construct(
            message: "Limite de {$limit} webhooks por organização atingido.",
            errorCode: 'WEBHOOK_LIMIT_EXCEEDED',
        );
    }
}
