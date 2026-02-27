<?php

declare(strict_types=1);

namespace App\Application\PaidAdvertising\DTOs;

final readonly class HandleAdAccountCallbackInput
{
    public function __construct(
        public string $code,
        public string $state,
    ) {}
}
