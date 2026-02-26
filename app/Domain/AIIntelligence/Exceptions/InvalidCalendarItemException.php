<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidCalendarItemException extends DomainException
{
    public function __construct(string $message = 'Invalid calendar item.')
    {
        parent::__construct($message, 'INVALID_CALENDAR_ITEM');
    }
}
