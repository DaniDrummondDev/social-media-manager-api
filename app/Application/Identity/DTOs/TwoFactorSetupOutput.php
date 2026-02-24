<?php

declare(strict_types=1);

namespace App\Application\Identity\DTOs;

final readonly class TwoFactorSetupOutput
{
    public function __construct(
        public string $secret,
        public string $qrCodeUrl,
        public string $qrCodeSvg,
    ) {}
}
