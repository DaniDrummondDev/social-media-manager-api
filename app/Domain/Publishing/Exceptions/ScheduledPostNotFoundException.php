<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ScheduledPostNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(
            message: "Scheduled post '{$id}' not found.",
            errorCode: 'SCHEDULED_POST_NOT_FOUND',
        );
    }
}
