<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ContentNotFoundException extends DomainException
{
    public function __construct(string $contentId)
    {
        parent::__construct(
            message: "Content not found: {$contentId}",
            errorCode: 'CONTENT_NOT_FOUND',
        );
    }
}
