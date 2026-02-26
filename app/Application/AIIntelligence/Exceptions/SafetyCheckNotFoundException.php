<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class SafetyCheckNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Safety check not found.')
    {
        parent::__construct($message, 'SAFETY_CHECK_NOT_FOUND');
    }
}
