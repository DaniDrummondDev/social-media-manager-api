<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class UpdateOrganizationInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $timezone = null,
    ) {}
}
