<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class FileSizeExceededException extends DomainException
{
    public function __construct(string $message = 'File size limit exceeded.')
    {
        parent::__construct(
            message: $message,
            errorCode: 'FILE_SIZE_EXCEEDED',
        );
    }
}
