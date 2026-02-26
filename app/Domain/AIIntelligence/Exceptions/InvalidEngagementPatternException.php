<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidEngagementPatternException extends DomainException
{
    public function __construct(string $message = 'Invalid engagement pattern.')
    {
        parent::__construct($message, 'INVALID_ENGAGEMENT_PATTERN');
    }
}
