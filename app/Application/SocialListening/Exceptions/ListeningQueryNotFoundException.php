<?php

declare(strict_types=1);

namespace App\Application\SocialListening\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ListeningQueryNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Query de listening não encontrada.')
    {
        parent::__construct($message, 'LISTENING_QUERY_NOT_FOUND');
    }
}
