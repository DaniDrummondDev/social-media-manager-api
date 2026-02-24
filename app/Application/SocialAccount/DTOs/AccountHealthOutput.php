<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class AccountHealthOutput
{
    public function __construct(
        public string $accountId,
        public string $status,
        public bool $canPublish,
        public ?string $tokenExpiresAt,
        public bool $isExpired,
        public bool $willExpireSoon,
    ) {}
}
