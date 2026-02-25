<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class AdminOrganizationDetailOutput
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $createdAt,
        public ?string $suspendedAt,
        public ?string $suspensionReason,
        public array $members,
        public ?array $subscription,
        public array $usage,
        public array $socialAccounts,
    ) {}
}
