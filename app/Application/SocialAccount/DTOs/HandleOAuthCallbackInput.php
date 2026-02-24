<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class HandleOAuthCallbackInput
{
    public function __construct(
        public string $code,
        public string $state,
    ) {}
}
