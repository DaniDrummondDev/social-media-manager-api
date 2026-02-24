<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Repositories;

use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Infrastructure\Identity\Models\RefreshTokenModel;
use DateTimeImmutable;

final class EloquentRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(
        private readonly RefreshTokenModel $model,
    ) {}

    public function store(
        string $id,
        string $userId,
        string $tokenHash,
        string $familyId,
        DateTimeImmutable $expiresAt,
    ): void {
        $this->model->newQuery()->create([
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'family_id' => $familyId,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{id: string, user_id: string, token_hash: string, family_id: string, expires_at: string, revoked_at: ?string}|null
     */
    public function findByTokenHash(string $tokenHash): ?array
    {
        /** @var RefreshTokenModel|null $record */
        $record = $this->model->newQuery()
            ->where('token_hash', $tokenHash)
            ->first();

        if ($record === null) {
            return null;
        }

        return [
            'id' => $record->getAttribute('id'),
            'user_id' => $record->getAttribute('user_id'),
            'token_hash' => $record->getAttribute('token_hash'),
            'family_id' => $record->getAttribute('family_id'),
            'expires_at' => $record->getAttribute('expires_at')?->toIso8601String() ?? '',
            'revoked_at' => $record->getAttribute('revoked_at')?->toIso8601String(),
        ];
    }

    public function revokeById(string $id): void
    {
        $this->model->newQuery()
            ->where('id', $id)
            ->update(['revoked_at' => now()]);
    }

    public function revokeByFamily(string $familyId): void
    {
        $this->model->newQuery()
            ->where('family_id', $familyId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllForUser(string $userId): void
    {
        $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }
}
