<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Services;

use App\Application\PaidAdvertising\Contracts\AdOAuthStateServiceInterface;
use Illuminate\Contracts\Cache\Repository;

final class AdOAuthStateService implements AdOAuthStateServiceInterface
{
    private const string CACHE_PREFIX = 'ad_oauth_state:';

    public function __construct(
        private readonly Repository $cache,
    ) {}

    public function generateState(string $organizationId, string $userId, string $provider): string
    {
        $state = bin2hex(random_bytes(32));

        $ttl = (int) config('ads.oauth.state_ttl', 600);

        $this->cache->put(
            self::CACHE_PREFIX.$state,
            json_encode([
                'organizationId' => $organizationId,
                'userId' => $userId,
                'provider' => $provider,
            ]),
            $ttl,
        );

        return $state;
    }

    /**
     * @return array{organizationId: string, userId: string, provider: string}|null
     */
    public function validateAndConsumeState(string $state): ?array
    {
        $key = self::CACHE_PREFIX.$state;

        $data = $this->cache->pull($key);

        if ($data === null) {
            return null;
        }

        /** @var array{organizationId: string, userId: string, provider: string} $decoded */
        $decoded = json_decode($data, true);

        return $decoded;
    }
}
