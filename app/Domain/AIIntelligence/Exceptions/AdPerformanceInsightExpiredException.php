<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AdPerformanceInsightExpiredException extends DomainException
{
    public function __construct(string $message = 'Ad performance insight has expired.')
    {
        parent::__construct($message, 'AD_PERFORMANCE_INSIGHT_EXPIRED');
    }
}
