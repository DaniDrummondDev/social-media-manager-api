<?php

declare(strict_types=1);

namespace App\Domain\Identity\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidUserStatusException extends DomainException
{
    public function __construct(string $currentStatus, string $targetStatus)
    {
        parent::__construct(
            message: "Cannot transition from {$currentStatus} to {$targetStatus}",
            errorCode: 'INVALID_USER_STATUS_TRANSITION',
        );
    }
}
