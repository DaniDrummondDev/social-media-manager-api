<?php

declare(strict_types=1);

namespace App\Domain\Billing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SubscriptionAlreadyCanceledException extends DomainException
{
    public function __construct(string $message = 'A assinatura já foi cancelada.')
    {
        parent::__construct($message, 'SUBSCRIPTION_ALREADY_CANCELED');
    }
}
