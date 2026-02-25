<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidSubscriptionTransitionException extends DomainException
{
    public function __construct(string $message = 'Transição de status inválida para a assinatura.')
    {
        parent::__construct($message, 'INVALID_SUBSCRIPTION_TRANSITION');
    }
}
