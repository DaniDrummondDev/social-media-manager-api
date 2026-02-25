<?php

declare(strict_types=1);

namespace App\Infrastructure\Publishing\Services;

use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use Illuminate\Support\Facades\Cache;

final class CircuitBreakerService
{
    private const int DEFAULT_FAILURE_THRESHOLD = 5;

    private const int DEFAULT_OPEN_TIMEOUT = 300;

    public function isOpen(SocialProvider $provider): bool
    {
        /** @var int|null $openUntil */
        $openUntil = Cache::store('redis')->get($this->openUntilKey($provider));

        if ($openUntil === null) {
            return false;
        }

        return time() < $openUntil;
    }

    public function recordFailure(SocialProvider $provider): void
    {
        $key = $this->failuresKey($provider);
        $timeout = $this->openTimeout();

        /** @var int $failures */
        $failures = Cache::store('redis')->increment($key);

        if ($failures === 1) {
            Cache::store('redis')->put($key, $failures, $timeout);
        }

        if ($failures >= $this->failureThreshold()) {
            Cache::store('redis')->put(
                $this->openUntilKey($provider),
                time() + $timeout,
                $timeout,
            );
        }
    }

    public function recordSuccess(SocialProvider $provider): void
    {
        Cache::store('redis')->forget($this->failuresKey($provider));
        Cache::store('redis')->forget($this->openUntilKey($provider));
    }

    private function failuresKey(SocialProvider $provider): string
    {
        return "circuit_breaker:{$provider->value}:failures";
    }

    private function openUntilKey(SocialProvider $provider): string
    {
        return "circuit_breaker:{$provider->value}:open_until";
    }

    private function failureThreshold(): int
    {
        return (int) config('social-media.circuit_breaker.failure_threshold', self::DEFAULT_FAILURE_THRESHOLD);
    }

    private function openTimeout(): int
    {
        return (int) config('social-media.circuit_breaker.open_timeout', self::DEFAULT_OPEN_TIMEOUT);
    }
}
