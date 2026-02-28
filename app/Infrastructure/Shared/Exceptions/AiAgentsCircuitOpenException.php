<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Exceptions;

use RuntimeException;

final class AiAgentsCircuitOpenException extends RuntimeException
{
    public function __construct(string $pipeline)
    {
        parent::__construct("AI Agents circuit breaker is open for pipeline: {$pipeline}");
    }
}
