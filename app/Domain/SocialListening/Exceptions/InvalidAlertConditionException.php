<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidAlertConditionException extends DomainException
{
    public function __construct(string $message = 'Condição de alerta inválida.')
    {
        parent::__construct($message, 'INVALID_ALERT_CONDITION');
    }
}
