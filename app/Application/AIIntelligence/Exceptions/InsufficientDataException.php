<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class InsufficientDataException extends ApplicationException
{
    public function __construct(string $message = 'Insufficient data to generate recommendation.')
    {
        parent::__construct($message, 'INSUFFICIENT_DATA');
    }
}
