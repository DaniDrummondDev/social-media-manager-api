<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Resources;

use App\Application\SocialAccount\DTOs\SocialAccountOutput;

final readonly class SocialAccountResource
{
    private function __construct(
        private string $id,
        private string $organizationId,
        private string $provider,
        private string $providerUserId,
        private string $username,
        private ?string $displayName,
        private ?string $profilePictureUrl,
        private string $status,
        private ?string $lastSyncedAt,
        private string $connectedAt,
        private string $createdAt,
    ) {}

    public static function fromOutput(SocialAccountOutput $output): self
    {
        return new self(
            id: $output->id,
            organizationId: $output->organizationId,
            provider: $output->provider,
            providerUserId: $output->providerUserId,
            username: $output->username,
            displayName: $output->displayName,
            profilePictureUrl: $output->profilePictureUrl,
            status: $output->status,
            lastSyncedAt: $output->lastSyncedAt,
            connectedAt: $output->connectedAt,
            createdAt: $output->createdAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'provider' => $this->provider,
            'provider_user_id' => $this->providerUserId,
            'username' => $this->username,
            'display_name' => $this->displayName,
            'profile_picture_url' => $this->profilePictureUrl,
            'status' => $this->status,
            'last_synced_at' => $this->lastSyncedAt,
            'connected_at' => $this->connectedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
