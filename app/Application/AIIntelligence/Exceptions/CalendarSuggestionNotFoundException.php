<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CalendarSuggestionNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Calendar suggestion not found.')
    {
        parent::__construct($message, 'CALENDAR_SUGGESTION_NOT_FOUND');
    }
}
