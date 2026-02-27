<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class ConnectAdAccountOutput
{
    public function __construct(
        public string $authorizationUrl,
        public string $state,
    ) {}
}
