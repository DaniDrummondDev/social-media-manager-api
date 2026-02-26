<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidGapAnalysisStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Cannot transition gap analysis status from '{$from}' to '{$to}'.",
            'INVALID_GAP_ANALYSIS_STATUS_TRANSITION',
        );
    }
}
