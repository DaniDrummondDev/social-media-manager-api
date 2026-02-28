<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Exceptions;

use RuntimeException;

final class AiAgentsTimeoutException extends RuntimeException
{
    public function __construct(string $pipeline, string $jobId)
    {
        parent::__construct("AI Agents timeout waiting for pipeline '{$pipeline}' job '{$jobId}'");
    }
}
