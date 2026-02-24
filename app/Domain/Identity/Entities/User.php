<?php

declare(strict_types=1);

namespace App\Domain\Identity\Entities;

use App\Domain\Identity\Events\TwoFactorDisabled;
use App\Domain\Identity\Events\TwoFactorEnabled;
use App\Domain\Identity\Events\UserEmailVerified;
use App\Domain\Identity\Events\UserLoggedIn;
use App\Domain\Identity\Events\UserPasswordChanged;
use App\Domain\Identity\Events\UserProfileUpdated;
use App\Domain\Identity\Events\UserRegistered;
use App\Domain\Identity\Exceptions\InvalidUserStatusException;
use App\Domain\Identity\Exceptions\UserAlreadyVerifiedException;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\Events\DomainEvent;
use App\Domain\Shared\ValueObjects\Uuid;
use DateTimeImmutable;

final readonly class User
{
    /**
     * @param  array<DomainEvent>  $domainEvents
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public Email $email,
        public HashedPassword $password,
        public ?string $phone,
        public string $timezone,
        public ?DateTimeImmutable $emailVerifiedAt,
        public bool $twoFactorEnabled,
        public ?TwoFactorSecret $twoFactorSecret,
        public ?string $recoveryCodes,
        public UserStatus $status,
        public ?DateTimeImmutable $lastLoginAt,
        public ?string $lastLoginIp,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public array $domainEvents = [],
    ) {}

    public static function create(
        string $name,
        Email $email,
        HashedPassword $password,
        string $timezone = 'America/Sao_Paulo',
        ?string $phone = null,
    ): self {
        $id = Uuid::generate();
        $now = new DateTimeImmutable;

        return new self(
            id: $id,
            name: $name,
            email: $email,
            password: $password,
            phone: $phone,
            timezone: $timezone,
            emailVerifiedAt: null,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            recoveryCodes: null,
            status: UserStatus::Active,
            lastLoginAt: null,
            lastLoginIp: null,
            createdAt: $now,
            updatedAt: $now,
            domainEvents: [
                new UserRegistered(
                    aggregateId: (string) $id,
                    organizationId: '',
                    userId: (string) $id,
                    email: (string) $email,
                ),
            ],
        );
    }

    public static function reconstitute(
        Uuid $id,
        string $name,
        Email $email,
        HashedPassword $password,
        ?string $phone,
        string $timezone,
        ?DateTimeImmutable $emailVerifiedAt,
        bool $twoFactorEnabled,
        ?TwoFactorSecret $twoFactorSecret,
        ?string $recoveryCodes,
        UserStatus $status,
        ?DateTimeImmutable $lastLoginAt,
        ?string $lastLoginIp,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            password: $password,
            phone: $phone,
            timezone: $timezone,
            emailVerifiedAt: $emailVerifiedAt,
            twoFactorEnabled: $twoFactorEnabled,
            twoFactorSecret: $twoFactorSecret,
            recoveryCodes: $recoveryCodes,
            status: $status,
            lastLoginAt: $lastLoginAt,
            lastLoginIp: $lastLoginIp,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function verifyEmail(): self
    {
        if ($this->emailVerifiedAt !== null) {
            throw new UserAlreadyVerifiedException;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $now,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new UserEmailVerified(
                    aggregateId: (string) $this->id,
                    organizationId: '',
                    userId: (string) $this->id,
                ),
            ],
        );
    }

    public function changePassword(HashedPassword $newPassword): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $newPassword,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new UserPasswordChanged(
                    aggregateId: (string) $this->id,
                    organizationId: '',
                    userId: (string) $this->id,
                ),
            ],
        );
    }

    public function updateProfile(
        ?string $name = null,
        ?string $phone = null,
        ?string $timezone = null,
    ): self {
        $changes = [];

        if ($name !== null && $name !== $this->name) {
            $changes['name'] = $name;
        }
        if ($phone !== null && $phone !== $this->phone) {
            $changes['phone'] = $phone;
        }
        if ($timezone !== null && $timezone !== $this->timezone) {
            $changes['timezone'] = $timezone;
        }

        if ($changes === []) {
            return $this;
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $changes['name'] ?? $this->name,
            email: $this->email,
            password: $this->password,
            phone: array_key_exists('phone', $changes) ? $changes['phone'] : $this->phone,
            timezone: $changes['timezone'] ?? $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new UserProfileUpdated(
                    aggregateId: (string) $this->id,
                    organizationId: '',
                    userId: (string) $this->id,
                    changes: $changes,
                ),
            ],
        );
    }

    public function recordLogin(string $ip, string $userAgent, string $organizationId): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $this->status,
            lastLoginAt: $now,
            lastLoginIp: $ip,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new UserLoggedIn(
                    aggregateId: (string) $this->id,
                    organizationId: $organizationId,
                    userId: (string) $this->id,
                    ipAddress: $ip,
                    userAgent: $userAgent,
                ),
            ],
        );
    }

    public function enableTwoFactor(TwoFactorSecret $secret, string $recoveryCodes): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: true,
            twoFactorSecret: $secret,
            recoveryCodes: $recoveryCodes,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new TwoFactorEnabled(
                    aggregateId: (string) $this->id,
                    organizationId: '',
                    userId: (string) $this->id,
                ),
            ],
        );
    }

    public function disableTwoFactor(): self
    {
        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: false,
            twoFactorSecret: null,
            recoveryCodes: null,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: [
                ...$this->domainEvents,
                new TwoFactorDisabled(
                    aggregateId: (string) $this->id,
                    organizationId: '',
                    userId: (string) $this->id,
                ),
            ],
        );
    }

    public function changeStatus(UserStatus $newStatus): self
    {
        if (! $this->status->canTransitionTo($newStatus)) {
            throw new InvalidUserStatusException($this->status->value, $newStatus->value);
        }

        $now = new DateTimeImmutable;

        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $newStatus,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $now,
            domainEvents: $this->domainEvents,
        );
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function releaseEvents(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            password: $this->password,
            phone: $this->phone,
            timezone: $this->timezone,
            emailVerifiedAt: $this->emailVerifiedAt,
            twoFactorEnabled: $this->twoFactorEnabled,
            twoFactorSecret: $this->twoFactorSecret,
            recoveryCodes: $this->recoveryCodes,
            status: $this->status,
            lastLoginAt: $this->lastLoginAt,
            lastLoginIp: $this->lastLoginIp,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
        );
    }
}
