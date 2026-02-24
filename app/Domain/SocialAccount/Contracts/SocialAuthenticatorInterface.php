<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Contracts;

use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;

interface SocialAuthenticatorInterface
{
    /**
     * @param  string[]  $scopes
     */
    public function getAuthorizationUrl(string $state, array $scopes = []): string;

    public function handleCallback(string $code, string $state): OAuthCredentials;

    public function refreshToken(EncryptedToken $refreshToken): OAuthCredentials;

    public function revokeToken(EncryptedToken $accessToken): void;

    /**
     * @return array<string, mixed>
     */
    public function getAccountInfo(EncryptedToken $accessToken): array;
}
