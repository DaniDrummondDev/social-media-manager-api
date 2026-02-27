<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class TestAdAccountConnectionOutput
{
    public function __construct(
        public bool $isConnected,
        public string $providerAccountName,
        public ?string $error = null,
    ) {}
}
