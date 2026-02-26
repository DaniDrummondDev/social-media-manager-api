<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidCrmConnectionStatusTransitionException extends DomainException
{
    public function __construct(string $message = 'Transição de status da conexão CRM inválida.')
    {
        parent::__construct($message, 'INVALID_CRM_CONNECTION_STATUS_TRANSITION');
    }
}
