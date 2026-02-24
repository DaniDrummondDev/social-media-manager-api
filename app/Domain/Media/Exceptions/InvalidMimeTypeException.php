<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidMimeTypeException extends DomainException
{
    public function __construct(string $mimeType)
    {
        parent::__construct(
            message: "MIME type '{$mimeType}' is not supported.",
            errorCode: 'INVALID_MIME_TYPE',
        );
    }
}
