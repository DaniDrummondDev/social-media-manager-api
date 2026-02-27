<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class ConnectAdAccountInput
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
