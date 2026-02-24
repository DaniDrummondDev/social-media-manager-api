<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

final readonly class SocialAccountListOutput
{
    /**
     * @param  SocialAccountOutput[]  $accounts
     */
    public function __construct(
        public array $accounts,
    ) {}
}
