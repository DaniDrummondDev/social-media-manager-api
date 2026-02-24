<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class InitiateOAuthInput
{
    /**
     * @param  string[]  $scopes
     */
    public function __construct(
        public string $organizationId,
        public string $userId,
        public string $provider,
        public array $scopes = [],
    ) {}
}
