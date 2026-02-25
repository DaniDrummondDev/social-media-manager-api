<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminUserDetailOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $status,
        public bool $emailVerified,
        public ?string $emailVerifiedAt,
        public bool $twoFactorEnabled,
        public string $timezone,
        public ?string $lastLoginAt,
        public ?string $lastLoginIp,
        public ?string $bannedAt,
        public ?string $banReason,
        public string $createdAt,
        public array $organizations,
    ) {}
}
