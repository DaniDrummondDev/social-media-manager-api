<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class SafetyCheckAlreadyCompletedException extends DomainException
{
    public function __construct(string $message = 'Safety check has already been completed.')
    {
        parent::__construct($message, 'SAFETY_CHECK_ALREADY_COMPLETED');
    }
}
