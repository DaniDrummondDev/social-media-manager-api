<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class RecommendationExpiredException extends DomainException
{
    public function __construct(string $message = 'Posting time recommendation has expired.')
    {
        parent::__construct($message, 'RECOMMENDATION_EXPIRED');
    }
}
