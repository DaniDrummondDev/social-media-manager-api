<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class DuplicateMentionException extends ApplicationException
{
    public function __construct(string $message = 'Menção já existe.')
    {
        parent::__construct($message, 'DUPLICATE_MENTION');
    }
}
