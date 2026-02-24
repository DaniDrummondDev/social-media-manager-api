<?php

declare(strict_types=1);

namespace App\Domain\Media\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class MediaNotUsableException extends DomainException
{
    public function __construct(string $reason = 'Media is not usable.')
    {
        parent::__construct(
            message: $reason,
            errorCode: 'MEDIA_NOT_USABLE',
        );
    }
}
