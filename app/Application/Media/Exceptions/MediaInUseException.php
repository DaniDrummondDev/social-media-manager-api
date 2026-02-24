<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class MediaInUseException extends ApplicationException
{
    public function __construct(string $mediaId)
    {
        parent::__construct("Media {$mediaId} is in use and cannot be deleted.", 'MEDIA_IN_USE');
    }
}
