<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class UploadNotFoundException extends ApplicationException
{
    public function __construct(string $uploadId)
    {
        parent::__construct("Upload session {$uploadId} not found.", 'UPLOAD_NOT_FOUND');
    }
}
