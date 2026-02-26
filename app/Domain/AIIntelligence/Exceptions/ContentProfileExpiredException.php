<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ContentProfileExpiredException extends DomainException
{
    public function __construct(string $message = 'Content profile has expired.')
    {
        parent::__construct($message, 'CONTENT_PROFILE_EXPIRED');
    }
}
