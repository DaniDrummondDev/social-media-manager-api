<?php

declare(strict_types=1);

namespace App\Domain\Engagement\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CommentAlreadyRepliedException extends DomainException
{
    public function __construct(string $message = 'Comentário já foi respondido.')
    {
        parent::__construct($message, 'COMMENT_ALREADY_REPLIED');
    }
}
