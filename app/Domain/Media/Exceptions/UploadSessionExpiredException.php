<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class UploadSessionExpiredException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Upload session has expired.',
            errorCode: 'UPLOAD_SESSION_EXPIRED',
        );
    }
}
