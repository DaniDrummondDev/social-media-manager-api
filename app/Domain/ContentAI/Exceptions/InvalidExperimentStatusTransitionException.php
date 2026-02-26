<?php

declare(strict_types=1);

namespace App\Domain\ContentAI\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidExperimentStatusTransitionException extends DomainException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(
            "Cannot transition experiment status from '{$from}' to '{$to}'.",
            'INVALID_EXPERIMENT_STATUS_TRANSITION',
        );
    }
}
