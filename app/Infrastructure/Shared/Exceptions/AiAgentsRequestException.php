<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Exceptions;

use RuntimeException;

final class AiAgentsRequestException extends RuntimeException
{
    public function __construct(string $pipeline, string $reason)
    {
        parent::__construct("AI Agents request failed for pipeline '{$pipeline}': {$reason}");
    }
}
