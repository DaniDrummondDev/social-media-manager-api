<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class GapAnalysisNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Gap analysis not found.')
    {
        parent::__construct($message, 'GAP_ANALYSIS_NOT_FOUND');
    }
}
