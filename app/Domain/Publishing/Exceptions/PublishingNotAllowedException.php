<?php

declare(strict_types=1);

namespace App\Domain\Publishing\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PublishingNotAllowedException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            errorCode: 'PUBLISHING_NOT_ALLOWED',
        );
    }
}
