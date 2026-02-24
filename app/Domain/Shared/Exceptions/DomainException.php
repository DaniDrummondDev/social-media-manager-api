<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'DOMAIN_ERROR',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
