<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidContentFingerprintException extends DomainException
{
    public function __construct(string $message = 'Invalid content fingerprint.')
    {
        parent::__construct($message, 'INVALID_CONTENT_FINGERPRINT');
    }
}
