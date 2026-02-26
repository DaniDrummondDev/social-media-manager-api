<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CalendarSuggestionExpiredException extends DomainException
{
    public function __construct(string $message = 'Calendar suggestion has expired.')
    {
        parent::__construct($message, 'CALENDAR_SUGGESTION_EXPIRED');
    }
}
