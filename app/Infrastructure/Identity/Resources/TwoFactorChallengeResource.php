<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Resources;

use App\Application\Identity\DTOs\TwoFactorChallengeOutput;

final readonly class TwoFactorChallengeResource
{
    private function __construct(
        private bool $requires_2fa,
        private string $temp_token,
    ) {}

    public static function fromOutput(TwoFactorChallengeOutput $output): self
    {
        return new self(
            requires_2fa: $output->requires2fa,
            temp_token: $output->tempToken,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'requires_2fa' => $this->requires_2fa,
            'temp_token' => $this->temp_token,
        ];
    }
}
