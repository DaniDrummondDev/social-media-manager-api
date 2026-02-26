<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class AudienceInsightNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Audience insight not found.')
    {
        parent::__construct($message, 'AUDIENCE_INSIGHT_NOT_FOUND');
    }
}
