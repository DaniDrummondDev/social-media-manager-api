<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class ListUsersAdminInput
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
        public ?bool $emailVerified = null,
        public ?bool $twoFactor = null,
        public ?string $from = null,
        public ?string $to = null,
        public string $sort = '-created_at',
        public int $perPage = 20,
        public ?string $cursor = null,
    ) {}
}
