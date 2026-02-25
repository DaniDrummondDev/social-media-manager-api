<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidExportStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            message: "Invalid export status transition from '{$from}' to '{$to}'.",
            errorCode: 'INVALID_EXPORT_STATUS_TRANSITION',
        );
    }
}
