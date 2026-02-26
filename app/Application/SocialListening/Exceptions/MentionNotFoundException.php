<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class MentionNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Menção não encontrada.')
    {
        parent::__construct($message, 'MENTION_NOT_FOUND');
    }
}
