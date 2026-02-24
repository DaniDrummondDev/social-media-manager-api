<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class CheckAccountHealthInput
{
    public function __construct(
        public string $organizationId,
        public string $accountId,
    ) {}
}
