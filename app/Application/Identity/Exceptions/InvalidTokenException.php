<?php

declare(strict_types=1);

namespace App\Application\Identity\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class InvalidTokenException extends ApplicationException
{
    public function __construct(string $tokenType = 'token')
    {
        parent::__construct("Invalid or expired {$tokenType}", 'INVALID_TOKEN');
    }
}
