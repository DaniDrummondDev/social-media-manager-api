<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity\Repositories;

use App\Domain\Identity\Entities\User;
use App\Domain\Identity\Repositories\UserRepositoryInterface;
use App\Domain\Identity\ValueObjects\Email;
use App\Domain\Identity\ValueObjects\HashedPassword;
use App\Domain\Identity\ValueObjects\TwoFactorSecret;
use App\Domain\Identity\ValueObjects\UserStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Identity\Models\UserModel;
use DateTimeImmutable;

final class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserModel $model,
    ) {}

    public function create(User $user): void
    {
        $model = $this->model->newInstance();
        $model->forceFill($this->toArray($user));
        $model->save();
    }

    public function update(User $user): void
    {
        $this->model->newQuery()
            ->where('id', (string) $user->id)
            ->update($this->toArray($user));
    }

    public function findById(Uuid $id): ?User
    {
        /** @var UserModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByEmail(Email $email): ?User
    {
        /** @var UserModel|null $record */
        $record = $this->model->newQuery()
            ->where('email', (string) $email)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->model->newQuery()
            ->where('email', (string) $email)
            ->exists();
    }

    private function toDomain(UserModel $model): User
    {
        $twoFactorSecret = $model->getAttribute('two_factor_secret')
            ? new TwoFactorSecret($model->getAttribute('two_factor_secret'))
            : null;

        $recoveryCodes = $model->getAttribute('recovery_codes');
        if (is_array($recoveryCodes)) {
            $recoveryCodes = json_encode($recoveryCodes, JSON_THROW_ON_ERROR);
        }

        return User::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            name: $model->getAttribute('name'),
            email: Email::fromString($model->getAttribute('email')),
            password: HashedPassword::fromHash($model->getAttribute('password')),
            phone: $model->getAttribute('phone'),
            timezone: $model->getAttribute('timezone'),
            emailVerifiedAt: $model->getAttribute('email_verified_at')
                ? new DateTimeImmutable($model->getAttribute('email_verified_at')->toDateTimeString())
                : null,
            twoFactorEnabled: (bool) $model->getAttribute('two_factor_enabled'),
            twoFactorSecret: $twoFactorSecret,
            recoveryCodes: $recoveryCodes,
            status: UserStatus::from($model->getAttribute('status')),
            lastLoginAt: $model->getAttribute('last_login_at')
                ? new DateTimeImmutable($model->getAttribute('last_login_at')->toDateTimeString())
                : null,
            lastLoginIp: $model->getAttribute('last_login_ip'),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => (string) $user->email,
            'password' => (string) $user->password,
            'phone' => $user->phone,
            'timezone' => $user->timezone,
            'email_verified_at' => $user->emailVerifiedAt?->format('Y-m-d H:i:s'),
            'two_factor_enabled' => $user->twoFactorEnabled,
            'two_factor_secret' => $user->twoFactorSecret ? (string) $user->twoFactorSecret : null,
            'recovery_codes' => $user->recoveryCodes,
            'status' => $user->status->value,
            'last_login_at' => $user->lastLoginAt?->format('Y-m-d H:i:s'),
            'last_login_ip' => $user->lastLoginIp,
        ];
    }
}
