<?php

declare(strict_types=1);

namespace App\Application\Engagement\DTOs;

final readonly class HandleCrmCallbackInput
{
    public function __construct(
        public string $code,
        public string $state,
    ) {}
}
