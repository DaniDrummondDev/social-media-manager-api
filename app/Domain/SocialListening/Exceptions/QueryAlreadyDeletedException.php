<?php

declare(strict_types=1);

namespace App\Domain\SocialListening\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class QueryAlreadyDeletedException extends DomainException
{
    public function __construct(string $message = 'Query já foi deletada.')
    {
        parent::__construct($message, 'QUERY_ALREADY_DELETED');
    }
}
