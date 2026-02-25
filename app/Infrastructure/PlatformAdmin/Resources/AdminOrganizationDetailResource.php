<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AdminOrganizationDetailOutput;

final readonly class AdminOrganizationDetailResource
{
    private function __construct(
        private AdminOrganizationDetailOutput $output,
    ) {}

    public static function fromOutput(AdminOrganizationDetailOutput $output): self
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
                'created_at' => $this->output->createdAt,
                'suspended_at' => $this->output->suspendedAt,
                'suspension_reason' => $this->output->suspensionReason,
                'members' => $this->output->members,
                'subscription' => $this->output->subscription,
                'usage' => $this->output->usage,
                'social_accounts' => $this->output->socialAccounts,
            ],
        ];
    }
}
