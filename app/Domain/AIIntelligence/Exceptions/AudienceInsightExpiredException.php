<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AudienceInsightExpiredException extends DomainException
{
    public function __construct(string $message = 'Audience insight has expired.')
    {
        parent::__construct($message, 'AUDIENCE_INSIGHT_EXPIRED');
    }
}
