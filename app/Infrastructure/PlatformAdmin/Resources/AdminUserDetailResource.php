<?php

declare(strict_types=1);

namespace App\Infrastructure\PlatformAdmin\Resources;

use App\Application\PlatformAdmin\DTOs\AdminUserDetailOutput;

final readonly class AdminUserDetailResource
{
    private function __construct(
        private AdminUserDetailOutput $output,
    ) {}

    public static function fromOutput(AdminUserDetailOutput $output): self
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
                'email_verified_at' => $this->output->emailVerifiedAt,
                'two_factor_enabled' => $this->output->twoFactorEnabled,
                'timezone' => $this->output->timezone,
                'last_login_at' => $this->output->lastLoginAt,
                'last_login_ip' => $this->output->lastLoginIp,
                'banned_at' => $this->output->bannedAt,
                'ban_reason' => $this->output->banReason,
                'created_at' => $this->output->createdAt,
                'organizations' => $this->output->organizations,
            ],
        ];
    }
}
