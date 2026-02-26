<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Services;

use App\Application\Engagement\Contracts\CrmOAuthStateServiceInterface;
use Illuminate\Contracts\Cache\Repository;

final class CrmOAuthStateService implements CrmOAuthStateServiceInterface
{
    private const string CACHE_PREFIX = 'crm_oauth_state:';

    public function __construct(
        private readonly Repository $cache,
    ) {}

    public function generateState(string $organizationId, string $userId, string $provider): string
    {
        $state = bin2hex(random_bytes(32));

        $ttl = (int) config('social-media.oauth.state_ttl', 600);

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
