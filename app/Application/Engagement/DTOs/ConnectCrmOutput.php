<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class ConnectCrmOutput
{
    public function __construct(
        public string $authorizationUrl,
        public string $state,
    ) {}
}
