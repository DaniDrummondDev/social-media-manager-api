<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class MediaNotFoundException extends ApplicationException
{
    public function __construct(string $mediaId)
    {
        parent::__construct("Media {$mediaId} not found.", 'MEDIA_NOT_FOUND');
    }
}
