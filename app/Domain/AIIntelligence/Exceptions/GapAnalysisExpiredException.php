<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class GapAnalysisExpiredException extends DomainException
{
    public function __construct(string $message = 'Content gap analysis has expired.')
    {
        parent::__construct($message, 'GAP_ANALYSIS_EXPIRED');
    }
}
