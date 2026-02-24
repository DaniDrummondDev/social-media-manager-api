<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\ValueObjects;

use DateTimeImmutable;

final readonly class OAuthCredentials
{
    /**
     * @param  string[]  $scopes
     */
    private function __construct(
        public EncryptedToken $accessToken,
        public ?EncryptedToken $refreshToken,
        public ?DateTimeImmutable $expiresAt,
        public array $scopes,
    ) {}

    /**
     * @param  string[]  $scopes
     */
    public static function create(
        EncryptedToken $accessToken,
        ?EncryptedToken $refreshToken = null,
        ?DateTimeImmutable $expiresAt = null,
        array $scopes = [],
    ): self {
        return new self(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            expiresAt: $expiresAt,
            scopes: $scopes,
        );
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new DateTimeImmutable > $this->expiresAt;
    }

    public function willExpireSoon(int $minutes = 60): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        $threshold = (new DateTimeImmutable)->modify("+{$minutes} minutes");

        return $threshold > $this->expiresAt;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function hasRefreshToken(): bool
    {
        return $this->refreshToken !== null && ! $this->refreshToken->isEmpty();
    }
}
