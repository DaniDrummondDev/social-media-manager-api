<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

use App\Domain\Identity\Entities\User;

final readonly class UserOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public string $timezone,
        public bool $emailVerified,
        public bool $twoFactorEnabled,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: (string) $user->id,
            name: $user->name,
            email: (string) $user->email,
            phone: $user->phone,
            timezone: $user->timezone,
            emailVerified: $user->isEmailVerified(),
            twoFactorEnabled: $user->twoFactorEnabled,
            lastLoginAt: $user->lastLoginAt?->format('c'),
            createdAt: $user->createdAt->format('c'),
            updatedAt: $user->updatedAt->format('c'),
        );
    }
}
