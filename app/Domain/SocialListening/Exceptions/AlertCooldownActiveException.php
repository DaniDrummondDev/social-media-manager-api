<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AlertCooldownActiveException extends DomainException
{
    public function __construct(string $message = 'Alerta em período de cooldown.')
    {
        parent::__construct($message, 'ALERT_COOLDOWN_ACTIVE');
    }
}
