<?php

declare(strict_types=1);

namespace App\Application\Analytics\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class ExportNotFoundException extends ApplicationException
{
    public function __construct(string $exportId)
    {
        parent::__construct(
            message: "Export '{$exportId}' not found.",
            errorCode: 'EXPORT_NOT_FOUND',
        );
    }
}
