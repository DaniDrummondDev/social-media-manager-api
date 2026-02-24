<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class UpdateProfileInput
{
    public function __construct(
        public string $userId,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $timezone = null,
    ) {}
}
