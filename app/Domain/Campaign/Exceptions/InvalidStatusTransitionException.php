<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            message: "Invalid status transition from '{$from}' to '{$to}'.",
            errorCode: 'INVALID_STATUS_TRANSITION',
        );
    }
}
