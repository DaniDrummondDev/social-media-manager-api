<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class PerformancePredictionNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Performance prediction not found.')
    {
        parent::__construct($message, 'PREDICTION_NOT_FOUND');
    }
}
