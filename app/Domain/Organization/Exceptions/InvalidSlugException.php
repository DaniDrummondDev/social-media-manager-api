<?php

declare(strict_types=1);

namespace App\Domain\Organization\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidSlugException extends DomainException
{
    public function __construct(string $slug)
    {
        parent::__construct(
            message: "Invalid slug format: {$slug}",
            errorCode: 'INVALID_SLUG',
        );
    }
}
