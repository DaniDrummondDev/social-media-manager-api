<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminUserOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public bool $emailVerified,
        public bool $twoFactorEnabled,
        public int $organizationsCount,
        public ?string $lastLoginAt,
        public string $createdAt,
    ) {}
}
