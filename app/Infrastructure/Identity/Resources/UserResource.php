<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Resources;

use App\Application\Identity\DTOs\UserOutput;

final readonly class UserResource
{
    private function __construct(
        private string $id,
        private string $name,
        private string $email,
        private ?string $phone,
        private string $timezone,
        private bool $email_verified,
        private bool $two_factor_enabled,
        private ?string $last_login_at,
        private string $created_at,
        private string $updated_at,
    ) {}

    public static function fromOutput(UserOutput $output): self
    {
        return new self(
            id: $output->id,
            name: $output->name,
            email: $output->email,
            phone: $output->phone,
            timezone: $output->timezone,
            email_verified: $output->emailVerified,
            two_factor_enabled: $output->twoFactorEnabled,
            last_login_at: $output->lastLoginAt,
            created_at: $output->createdAt,
            updated_at: $output->updatedAt,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'email_verified' => $this->email_verified,
            'two_factor_enabled' => $this->two_factor_enabled,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
