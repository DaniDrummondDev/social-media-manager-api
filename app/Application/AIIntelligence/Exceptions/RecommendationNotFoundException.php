<?php

declare(strict_types=1);

namespace App\Application\AIIntelligence\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

final class RecommendationNotFoundException extends ApplicationException
{
    public function __construct(string $message = 'Posting time recommendation not found.')
    {
        parent::__construct($message, 'RECOMMENDATION_NOT_FOUND');
    }
}
