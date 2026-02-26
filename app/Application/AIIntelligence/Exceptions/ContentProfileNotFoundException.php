<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ContentProfileNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Content profile not found.')
    {
        parent::__construct($message, 'CONTENT_PROFILE_NOT_FOUND');
    }
}
