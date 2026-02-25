<?php

declare(strict_types=1);

namespace App\Domain\Billing\Events;

use App\Domain\Shared\Events\DomainEvent;

final readonly class PaymentFailed extends DomainEvent
{
    public function eventName(): string
    {
        return 'billing.payment_failed';
    }
}
