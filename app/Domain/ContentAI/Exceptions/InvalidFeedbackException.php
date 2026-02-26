<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidFeedbackException extends DomainException
{
    public function __construct(string $message = 'Invalid generation feedback data.')
    {
        parent::__construct($message, 'INVALID_FEEDBACK');
    }
}
