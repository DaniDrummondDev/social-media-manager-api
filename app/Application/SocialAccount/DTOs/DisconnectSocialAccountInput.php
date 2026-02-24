<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class DisconnectSocialAccountInput
{
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $accountId,
    ) {}
}
