<?php

declare(strict_types=1);

namespace App\Domain\AIIntelligence\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InsufficientEditDataException extends DomainException
{
    public function __construct(int $required = 10, int $actual = 0)
    {
        parent::__construct(
            "Minimum {$required} edited feedbacks required to generate style profile, got {$actual}.",
            'INSUFFICIENT_EDIT_DATA',
        );
    }
}
