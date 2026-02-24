<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class CreateOrganizationInput
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $slug,
        public string $timezone = 'America/Sao_Paulo',
    ) {}
}
