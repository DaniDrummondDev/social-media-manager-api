<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class WebhookNotFoundException extends ApplicationException
{
    public function __construct(string $webhookId)
    {
        parent::__construct(
            message: "Webhook '{$webhookId}' não encontrado.",
            errorCode: 'WEBHOOK_NOT_FOUND',
        );
    }
}
