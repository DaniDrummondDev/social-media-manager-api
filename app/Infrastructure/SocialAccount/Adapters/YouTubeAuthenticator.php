<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Adapters;

use App\Domain\SocialAccount\Contracts\SocialAuthenticatorInterface;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Infrastructure\SocialAccount\Services\SocialTokenEncrypter;
use RuntimeException;

final class YouTubeAuthenticator implements SocialAuthenticatorInterface
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
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => implode(' ', $resolvedScopes),
            'response_type' => 'code',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params);
    }

    public function handleCallback(string $code, string $state): OAuthCredentials
    {
        throw new RuntimeException('YouTubeAuthenticator::handleCallback() is not implemented yet.');
    }

    public function refreshToken(EncryptedToken $refreshToken): OAuthCredentials
    {
        throw new RuntimeException('YouTubeAuthenticator::refreshToken() is not implemented yet.');
    }

    public function revokeToken(EncryptedToken $accessToken): void
    {
        throw new RuntimeException('YouTubeAuthenticator::revokeToken() is not implemented yet.');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountInfo(EncryptedToken $accessToken): array
    {
        throw new RuntimeException('YouTubeAuthenticator::getAccountInfo() is not implemented yet.');
    }
}
