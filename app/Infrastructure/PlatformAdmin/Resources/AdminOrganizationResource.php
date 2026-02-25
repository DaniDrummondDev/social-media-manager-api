<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AdminOrganizationOutput;

final readonly class AdminOrganizationResource
{
    private function __construct(
        private AdminOrganizationOutput $output,
    ) {}

    public static function fromOutput(AdminOrganizationOutput $output): self
    {
        return new self($output);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->output->id,
            'type' => 'organization',
            'attributes' => [
                'name' => $this->output->name,
                'status' => $this->output->status,
                'plan' => $this->output->plan,
                'members_count' => $this->output->membersCount,
                'social_accounts_count' => $this->output->socialAccountsCount,
                'owner' => $this->output->owner,
                'subscription_status' => $this->output->subscriptionStatus,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
