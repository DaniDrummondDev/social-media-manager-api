<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Resources;

use App\Application\Identity\DTOs\TwoFactorSetupOutput;

final readonly class TwoFactorSetupResource
{
    private function __construct(
        private string $secret,
        private string $qr_code_url,
        private string $qr_code_svg,
    ) {}

    public static function fromOutput(TwoFactorSetupOutput $output): self
    {
        return new self(
            secret: $output->secret,
            qr_code_url: $output->qrCodeUrl,
            qr_code_svg: $output->qrCodeSvg,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'secret' => $this->secret,
            'qr_code_url' => $this->qr_code_url,
            'qr_code_svg' => $this->qr_code_svg,
        ];
    }
}
