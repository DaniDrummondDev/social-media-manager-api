<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class StripeWebhookInvalidSignatureException extends ApplicationException
{
    public function __construct(string $message = 'Assinatura do webhook Stripe inválida.')
    {
        parent::__construct($message, 'WEBHOOK_INVALID_SIGNATURE');
    }
}
