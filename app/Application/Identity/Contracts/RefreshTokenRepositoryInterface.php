<?php

declare(strict_types=1);

namespace App\Application\Identity\Contracts;

use DateTimeImmutable;

interface RefreshTokenRepositoryInterface
{
    public function store(
        string $id,
        string $userId,
        string $tokenHash,
        string $familyId,
        DateTimeImmutable $expiresAt,
    ): void;

    /**
     * @return array{id: string, user_id: string, token_hash: string, family_id: string, expires_at: string, revoked_at: ?string}|null
     */
    public function findByTokenHash(string $tokenHash): ?array;

    public function revokeById(string $id): void;

    public function revokeByFamily(string $familyId): void;

    public function revokeAllForUser(string $userId): void;
}
