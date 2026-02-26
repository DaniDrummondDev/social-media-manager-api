<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidPredictionBreakdownException extends DomainException
{
    public function __construct(string $message = 'Prediction breakdown scores must be between 0 and 100.')
    {
        parent::__construct($message, 'INVALID_PREDICTION_BREAKDOWN');
    }
}
