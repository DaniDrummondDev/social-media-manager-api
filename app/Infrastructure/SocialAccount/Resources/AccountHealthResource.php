<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Resources;

use App\Application\SocialAccount\DTOs\AccountHealthOutput;

final readonly class AccountHealthResource
{
    private function __construct(
        private string $accountId,
        private string $status,
        private bool $canPublish,
        private ?string $tokenExpiresAt,
        private bool $isExpired,
        private bool $willExpireSoon,
    ) {}

    public static function fromOutput(AccountHealthOutput $output): self
    {
        return new self(
            accountId: $output->accountId,
            status: $output->status,
            canPublish: $output->canPublish,
            tokenExpiresAt: $output->tokenExpiresAt,
            isExpired: $output->isExpired,
            willExpireSoon: $output->willExpireSoon,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'status' => $this->status,
            'can_publish' => $this->canPublish,
            'token_expires_at' => $this->tokenExpiresAt,
            'is_expired' => $this->isExpired,
            'will_expire_soon' => $this->willExpireSoon,
        ];
    }
}
