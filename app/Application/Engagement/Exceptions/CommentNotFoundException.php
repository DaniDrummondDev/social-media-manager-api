<?php

declare(strict_types=1);

namespace App\Application\Engagement\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class CommentNotFoundException extends ApplicationException
{
    public function __construct(string $commentId)
    {
        parent::__construct(
            message: "Comentário '{$commentId}' não encontrado.",
            errorCode: 'COMMENT_NOT_FOUND',
        );
    }
}
