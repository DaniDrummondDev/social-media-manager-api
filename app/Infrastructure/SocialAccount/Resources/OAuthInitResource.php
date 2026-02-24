<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Resources;

use App\Application\SocialAccount\DTOs\InitiateOAuthOutput;

final readonly class OAuthInitResource
{
    private function __construct(
        private string $authorizationUrl,
        private string $state,
    ) {}

    public static function fromOutput(InitiateOAuthOutput $output): self
    {
        return new self(
            authorizationUrl: $output->authorizationUrl,
            state: $output->state,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'authorization_url' => $this->authorizationUrl,
            'state' => $this->state,
        ];
    }
}
