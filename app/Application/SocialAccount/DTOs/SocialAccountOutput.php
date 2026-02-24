<?php

declare(strict_types=1);

namespace App\Application\SocialAccount\DTOs;

use App\Domain\SocialAccount\Entities\SocialAccount;

final readonly class SocialAccountOutput
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $provider,
        public string $providerUserId,
        public string $username,
        public ?string $displayName,
        public ?string $profilePictureUrl,
        public string $status,
        public ?string $lastSyncedAt,
        public string $connectedAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(SocialAccount $account): self
    {
        return new self(
            id: (string) $account->id,
            organizationId: (string) $account->organizationId,
            provider: $account->provider->value,
            providerUserId: $account->providerUserId,
            username: $account->username,
            displayName: $account->displayName,
            profilePictureUrl: $account->profilePictureUrl,
            status: $account->status->value,
            lastSyncedAt: $account->lastSyncedAt?->format('c'),
            connectedAt: $account->connectedAt->format('c'),
            createdAt: $account->createdAt->format('c'),
        );
    }
}
