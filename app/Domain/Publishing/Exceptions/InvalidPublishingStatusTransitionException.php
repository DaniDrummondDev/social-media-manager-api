<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidPublishingStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            message: "Invalid publishing status transition from '{$from}' to '{$to}'.",
            errorCode: 'INVALID_PUBLISHING_STATUS_TRANSITION',
        );
    }
}
