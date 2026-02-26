<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidTimeSlotException extends DomainException
{
    public function __construct(string $message = 'Invalid time slot.')
    {
        parent::__construct($message, 'INVALID_TIME_SLOT');
    }
}
