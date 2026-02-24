<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Adapters;

use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Infrastructure\SocialAccount\Services\SocialTokenEncrypter;
use RuntimeException;

final class TikTokAuthenticator implements SocialAuthenticatorInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly SocialTokenEncrypter $encrypter, // @phpstan-ignore property.onlyWritten
        private readonly array $config,
    ) {}

    /**
     * @param  string[]  $scopes
     */
    public function getAuthorizationUrl(string $state, array $scopes = []): string
    {
        $resolvedScopes = ! empty($scopes) ? $scopes : ($this->config['scopes'] ?? []);

        $params = [
            'client_key' => $this->config['client_key'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(',', $resolvedScopes),
            'response_type' => 'code',
            'state' => $state,
        ];

        return 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query($params);
    }

    public function handleCallback(string $code, string $state): OAuthCredentials
    {
        throw new RuntimeException('TikTokAuthenticator::handleCallback() is not implemented yet.');
    }

    public function refreshToken(EncryptedToken $refreshToken): OAuthCredentials
    {
        throw new RuntimeException('TikTokAuthenticator::refreshToken() is not implemented yet.');
    }

    public function revokeToken(EncryptedToken $accessToken): void
    {
        throw new RuntimeException('TikTokAuthenticator::revokeToken() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountInfo(EncryptedToken $accessToken): array
    {
        throw new RuntimeException('TikTokAuthenticator::getAccountInfo() is not implemented yet.');
    }
}
