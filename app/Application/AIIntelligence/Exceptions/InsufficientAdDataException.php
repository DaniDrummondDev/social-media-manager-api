<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class InsufficientAdDataException extends ApplicationException
{
    public function __construct(int $boostCount, int $required)
    {
        parent::__construct(
            "Insufficient ad data: {$boostCount} boosts, minimum {$required} required.",
            'INSUFFICIENT_AD_DATA',
        );
    }
}
