<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AdminUserOutput;

final readonly class AdminUserResource
{
    private function __construct(
        private AdminUserOutput $output,
    ) {}

    public static function fromOutput(AdminUserOutput $output): self
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
            'type' => 'user',
            'attributes' => [
                'name' => $this->output->name,
                'email' => $this->output->email,
                'status' => $this->output->status,
                'email_verified' => $this->output->emailVerified,
                'two_factor_enabled' => $this->output->twoFactorEnabled,
                'organizations_count' => $this->output->organizationsCount,
                'last_login_at' => $this->output->lastLoginAt,
                'created_at' => $this->output->createdAt,
            ],
        ];
    }
}
