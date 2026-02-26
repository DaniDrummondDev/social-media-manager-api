<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class OrgStyleProfileExpiredException extends DomainException
{
    public function __construct(string $message = 'Organization style profile has expired.')
    {
        parent::__construct($message, 'ORG_STYLE_PROFILE_EXPIRED');
    }
}
