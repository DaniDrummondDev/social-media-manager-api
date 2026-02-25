<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminOrganizationOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public ?string $plan,
        public int $membersCount,
        public int $socialAccountsCount,
        public ?array $owner,
        public ?string $subscriptionStatus,
        public string $createdAt,
    ) {}
}
