<?php

declare(strict_types=1);

namespace App\Domain\PlatformAdmin\Entities;

use App\Domain\PlatformAdmin\ValueObjects\PlatformRole;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class PlatformAdmin
{
    /**
     * @param  array<string, mixed>  $permissions
     */
    public function __construct(
        public Uuid $id,
        public Uuid $userId,
        public PlatformRole $role,
        public array $permissions,
        public bool $isActive,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    /**
     * @param  array<string, mixed>  $permissions
     */
    public static function reconstitute(
        Uuid $id,
        Uuid $userId,
        PlatformRole $role,
        array $permissions,
        bool $isActive,
        ?DateTimeImmutable $lastLoginAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            role: $role,
            permissions: $permissions,
            isActive: $isActive,
            lastLoginAt: $lastLoginAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            role: $this->role,
            permissions: $this->permissions,
            isActive: true,
            lastLoginAt: $this->lastLoginAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            role: $this->role,
            permissions: $this->permissions,
            isActive: false,
            lastLoginAt: $this->lastLoginAt,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }

    public function updateLastLogin(DateTimeImmutable $at): self
    {
        return new self(
            id: $this->id,
            userId: $this->userId,
            role: $this->role,
            permissions: $this->permissions,
            isActive: $this->isActive,
            lastLoginAt: $at,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable,
        );
    }
}
