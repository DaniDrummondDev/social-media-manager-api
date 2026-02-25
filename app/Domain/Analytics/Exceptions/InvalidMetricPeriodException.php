<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidMetricPeriodException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(
            message: $message,
            errorCode: 'INVALID_METRIC_PERIOD',
        );
    }
}
