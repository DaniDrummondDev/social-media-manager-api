<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Contracts;

interface AiAgentsCircuitBreakerInterface
{
    public function isOpen(string $pipeline): bool;

    public function recordFailure(string $pipeline): void;

    public function recordSuccess(string $pipeline): void;
}
