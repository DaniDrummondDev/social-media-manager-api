<?php

declare(strict_types=1);

namespace App\Application\Billing\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class SubscriptionNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Assinatura não encontrada.')
    {
        parent::__construct($message, 'SUBSCRIPTION_NOT_FOUND');
    }
}
