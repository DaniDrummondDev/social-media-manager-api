<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidSuggestionStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Invalid suggestion status transition from '{$from}' to '{$to}'.",
            'INVALID_SUGGESTION_STATUS_TRANSITION',
        );
    }
}
