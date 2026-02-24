<?php

declare(strict_types=1);

use App\Application\Identity\Contracts\RefreshTokenRepositoryInterface;
use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(RefreshTokenRepositoryInterface::class);

    // Create a user to satisfy foreign key
    $this->user = User::create(
        name: 'Token User',
        email: Email::fromString('token@example.com'),
        password: HashedPassword::fromPlainText('SecureP@ss1'),
    );
    app(UserRepositoryInterface::class)->create($this->user);
    $this->userId = (string) $this->user->id;
});

it('stores a refresh token', function () {
    $id = (string) Str::uuid();
    $tokenHash = hash('sha256', 'test-token');
    $familyId = (string) Str::uuid();

    $this->repository->store($id, $this->userId, $tokenHash, $familyId, new \DateTimeImmutable('+1 hour'));

    $found = $this->repository->findByTokenHash($tokenHash);

    expect($found)->not->toBeNull()
        ->and($found['id'])->toBe($id)
        ->and($found['user_id'])->toBe($this->userId)
        ->and($found['family_id'])->toBe($familyId)
        ->and($found['revoked_at'])->toBeNull();
});

it('returns null for non-existent token hash', function () {
    $found = $this->repository->findByTokenHash(hash('sha256', 'nonexistent'));

    expect($found)->toBeNull();
});

it('revokes a token by id', function () {
    $id = (string) Str::uuid();
    $tokenHash = hash('sha256', 'revoke-by-id');

    $this->repository->store($id, $this->userId, $tokenHash, (string) Str::uuid(), new \DateTimeImmutable('+1 hour'));
    $this->repository->revokeById($id);

    $found = $this->repository->findByTokenHash($tokenHash);

    expect($found['revoked_at'])->not->toBeNull();
});

it('revokes all tokens in a family', function () {
    $familyId = (string) Str::uuid();

    $this->repository->store((string) Str::uuid(), $this->userId, hash('sha256', 'family-1'), $familyId, new \DateTimeImmutable('+1 hour'));
    $this->repository->store((string) Str::uuid(), $this->userId, hash('sha256', 'family-2'), $familyId, new \DateTimeImmutable('+1 hour'));
    $this->repository->store((string) Str::uuid(), $this->userId, hash('sha256', 'other-family'), (string) Str::uuid(), new \DateTimeImmutable('+1 hour'));

    $this->repository->revokeByFamily($familyId);

    expect($this->repository->findByTokenHash(hash('sha256', 'family-1'))['revoked_at'])->not->toBeNull()
        ->and($this->repository->findByTokenHash(hash('sha256', 'family-2'))['revoked_at'])->not->toBeNull()
        ->and($this->repository->findByTokenHash(hash('sha256', 'other-family'))['revoked_at'])->toBeNull();
});

it('revokes all tokens for a user', function () {
    $this->repository->store((string) Str::uuid(), $this->userId, hash('sha256', 'user-1'), (string) Str::uuid(), new \DateTimeImmutable('+1 hour'));
    $this->repository->store((string) Str::uuid(), $this->userId, hash('sha256', 'user-2'), (string) Str::uuid(), new \DateTimeImmutable('+1 hour'));

    $this->repository->revokeAllForUser($this->userId);

    expect($this->repository->findByTokenHash(hash('sha256', 'user-1'))['revoked_at'])->not->toBeNull()
        ->and($this->repository->findByTokenHash(hash('sha256', 'user-2'))['revoked_at'])->not->toBeNull();
});
