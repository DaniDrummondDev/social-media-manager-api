<?php

declare(strict_types=1);

namespace App\Application\SocialListening\DTOs;

final readonly class PlatformBreakdownOutput
{
    public function __construct(
        public string $platform,
        public int $count,
        public float $percentage,
    ) {}
}
