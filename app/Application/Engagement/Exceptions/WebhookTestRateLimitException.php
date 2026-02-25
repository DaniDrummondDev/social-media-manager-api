<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class WebhookTestRateLimitException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Limite de testes de webhook atingido. Aguarde 15 minutos.',
            errorCode: 'WEBHOOK_TEST_RATE_LIMITED',
        );
    }
}
