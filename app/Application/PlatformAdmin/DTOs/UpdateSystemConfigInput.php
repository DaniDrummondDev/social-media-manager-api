<?php

declare(strict_types=1);

namespace App\Application\PlatformAdmin\DTOs;

final readonly class UpdateSystemConfigInput
{
    public function __construct(
        public array $configs,
    ) {}
}
