<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidBlacklistRegexException extends DomainException
{
    public function __construct(string $message = 'Expressão regular da blacklist inválida.')
    {
        parent::__construct($message, 'INVALID_BLACKLIST_REGEX');
    }
}
