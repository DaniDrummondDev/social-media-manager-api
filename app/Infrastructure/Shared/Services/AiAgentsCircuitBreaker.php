<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Services;

use App\Infrastructure\Shared\Contracts\AiAgentsCircuitBreakerInterface;
use Illuminate\Support\Facades\Cache;

final class AiAgentsCircuitBreaker implements AiAgentsCircuitBreakerInterface
{
    public function isOpen(string $pipeline): bool
    {
        /** @var int|null $openUntil */
        $openUntil = Cache::store('redis')->get($this->openUntilKey($pipeline));

        if ($openUntil === null) {
            return false;
        }

        return time() < $openUntil;
    }

    public function recordFailure(string $pipeline): void
    {
        $key = $this->failuresKey($pipeline);
        $timeout = $this->openTimeout();

        /** @var int $failures */
        $failures = Cache::store('redis')->increment($key);

        if ($failures === 1) {
            Cache::store('redis')->put($key, $failures, $timeout);
        }

        if ($failures >= $this->failureThreshold()) {
            Cache::store('redis')->put(
                $this->openUntilKey($pipeline),
                time() + $timeout,
                $timeout,
            );
        }
    }

    public function recordSuccess(string $pipeline): void
    {
        Cache::store('redis')->forget($this->failuresKey($pipeline));
        Cache::store('redis')->forget($this->openUntilKey($pipeline));
    }

    private function failuresKey(string $pipeline): string
    {
        return "circuit:ai_agents:{$pipeline}:failures";
    }

    private function openUntilKey(string $pipeline): string
    {
        return "circuit:ai_agents:{$pipeline}:open_until";
    }

    private function failureThreshold(): int
    {
        return (int) config('ai-agents.circuit_breaker.failure_threshold', 3);
    }

    private function openTimeout(): int
    {
        return (int) config('ai-agents.circuit_breaker.open_timeout', 120);
    }
}
