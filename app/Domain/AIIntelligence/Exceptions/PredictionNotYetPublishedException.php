<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PredictionNotYetPublishedException extends DomainException
{
    public function __construct(string $message = 'Prediction cannot be validated before content is published.')
    {
        parent::__construct($message, 'PREDICTION_NOT_YET_PUBLISHED');
    }
}
