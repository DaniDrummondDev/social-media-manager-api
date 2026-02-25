<?php

declare(strict_types=1);

namespace App\Application\Analytics\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ExportRateLimitExceededException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Export rate limit exceeded. Maximum 5 exports per hour.',
            errorCode: 'EXPORT_RATE_LIMIT_EXCEEDED',
        );
    }
}
