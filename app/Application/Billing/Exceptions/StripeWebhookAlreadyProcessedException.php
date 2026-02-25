<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class StripeWebhookAlreadyProcessedException extends ApplicationException
{
    public function __construct(string $message = 'Evento webhook já processado.')
    {
        parent::__construct($message, 'WEBHOOK_ALREADY_PROCESSED');
    }
}
