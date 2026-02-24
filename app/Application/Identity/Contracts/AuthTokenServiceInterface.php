<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

interface AuthTokenServiceInterface
{
    /**
     * @return array{token: string, jti: string, expires_in: int}
     */
    public function generateAccessToken(string $userId, string $organizationId, string $email, string $role): array;

    /**
     * @return string Opaque refresh token value (NOT hashed)
     */
    public function generateRefreshToken(): string;

    public function blacklistToken(string $jti, int $ttlSeconds): void;

    /**
     * @return array{sub: string, org: string, email: string, role: string, jti: string}|null
     */
    public function validateAccessToken(string $token): ?array;

    /**
     * @return array{token: string, expires_in: int}
     */
    public function generateTempToken(string $userId): array;

    public function validateTempToken(string $token): ?string;
}
