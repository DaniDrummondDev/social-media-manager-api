<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidQueryTransitionException extends DomainException
{
    public function __construct(string $message = 'Transição de status de query inválida.')
    {
        parent::__construct($message, 'INVALID_QUERY_TRANSITION');
    }
}
