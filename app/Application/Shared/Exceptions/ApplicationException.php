<?php

declare(strict_types=1);

namespace App\Application\Shared\Exceptions;

use RuntimeException;
use Throwable;

class ApplicationException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'APPLICATION_ERROR',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
