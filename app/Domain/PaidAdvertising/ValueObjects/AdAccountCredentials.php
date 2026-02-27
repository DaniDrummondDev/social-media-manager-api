<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\ValueObjects;

use DateTimeImmutable;

final readonly class AdAccountCredentials
{
    /**
     * @param  array<string>  $scopes
     */
    private function __construct(
        public string $encryptedAccessToken,
        public ?string $encryptedRefreshToken,
        public ?DateTimeImmutable $expiresAt,
        public array $scopes,
    ) {}

    /**
     * @param  array<string>  $scopes
     */
    public static function create(
        string $encryptedAccessToken,
        ?string $encryptedRefreshToken = null,
        ?DateTimeImmutable $expiresAt = null,
        array $scopes = [],
    ): self {
        return new self($encryptedAccessToken, $encryptedRefreshToken, $expiresAt, $scopes);
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

    public function hasRefreshToken(): bool
    {
        return $this->encryptedRefreshToken !== null && $this->encryptedRefreshToken !== '';
    }
}
