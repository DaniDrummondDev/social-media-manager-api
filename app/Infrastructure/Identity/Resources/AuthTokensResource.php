<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Resources;

use App\Application\Identity\DTOs\AuthTokensOutput;

final readonly class AuthTokensResource
{
    private function __construct(
        private string $access_token,
        private string $refresh_token,
        private string $token_type,
        private int $expires_in,
    ) {}

    public static function fromOutput(AuthTokensOutput $output): self
    {
        return new self(
            access_token: $output->accessToken,
            refresh_token: $output->refreshToken,
            token_type: $output->tokenType,
            expires_in: $output->expiresIn,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token,
            'token_type' => $this->token_type,
            'expires_in' => $this->expires_in,
        ];
    }
}
