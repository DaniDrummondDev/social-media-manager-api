<?php

declare(strict_types=1);

namespace App\Infrastructure\SocialAccount\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\Repositories\SocialAccountRepositoryInterface;
use App\Domain\SocialAccount\ValueObjects\ConnectionStatus;
use App\Domain\SocialAccount\ValueObjects\EncryptedToken;
use App\Domain\SocialAccount\ValueObjects\OAuthCredentials;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;
use App\Infrastructure\SocialAccount\Models\SocialAccountModel;
use DateTimeImmutable;

final class EloquentSocialAccountRepository implements SocialAccountRepositoryInterface
{
    public function __construct(
        private readonly SocialAccountModel $model,
    ) {}

    public function create(SocialAccount $account): void
    {
        $this->model->newQuery()->create($this->toArray($account));
    }

    public function update(SocialAccount $account): void
    {
        $this->model->newQuery()
            ->where('id', (string) $account->id)
            ->update($this->toArray($account));
    }

    public function findById(Uuid $id): ?SocialAccount
    {
        /** @var SocialAccountModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return SocialAccount[]
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $records */
        return $records->map(fn (SocialAccountModel $record) => $this->toDomain($record))->all();
    }

    /**
     * @return SocialAccount[]
     */
    public function findByOrganizationAndProvider(Uuid $organizationId, SocialProvider $provider): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('provider', $provider->value)
            ->whereNull('deleted_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $records */
        return $records->map(fn (SocialAccountModel $record) => $this->toDomain($record))->all();
    }

    public function findByProviderAndProviderUserId(SocialProvider $provider, string $providerUserId): ?SocialAccount
    {
        /** @var SocialAccountModel|null $record */
        $record = $this->model->newQuery()
            ->where('provider', $provider->value)
            ->where('provider_user_id', $providerUserId)
            ->whereNull('deleted_at')
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();
    }

    /**
     * @return SocialAccount[]
     */
    public function findExpiringTokens(int $minutesUntilExpiry): array
    {
        $threshold = (new DateTimeImmutable)->modify("+{$minutesUntilExpiry} minutes");

        $records = $this->model->newQuery()
            ->where('status', ConnectionStatus::Connected->value)
            ->whereNull('deleted_at')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $threshold->format('Y-m-d H:i:s'))
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $records */
        return $records->map(fn (SocialAccountModel $record) => $this->toDomain($record))->all();
    }

    private function toDomain(SocialAccountModel $model): SocialAccount
    {
        $refreshToken = $model->getAttribute('refresh_token')
            ? EncryptedToken::fromEncrypted($model->getAttribute('refresh_token'))
            : null;

        $credentials = OAuthCredentials::create(
            accessToken: EncryptedToken::fromEncrypted($model->getAttribute('access_token')),
            refreshToken: $refreshToken,
            expiresAt: $model->getAttribute('token_expires_at')
                ? new DateTimeImmutable($model->getAttribute('token_expires_at')->toDateTimeString())
                : null,
            scopes: $model->getAttribute('scopes') ?? [],
        );

        return SocialAccount::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            connectedBy: Uuid::fromString($model->getAttribute('connected_by')),
            provider: SocialProvider::from($model->getAttribute('provider')),
            providerUserId: $model->getAttribute('provider_user_id'),
            username: $model->getAttribute('username'),
            displayName: $model->getAttribute('display_name'),
            profilePictureUrl: $model->getAttribute('profile_picture_url'),
            credentials: $credentials,
            status: ConnectionStatus::from($model->getAttribute('status')),
            lastSyncedAt: $model->getAttribute('last_synced_at')
                ? new DateTimeImmutable($model->getAttribute('last_synced_at')->toDateTimeString())
                : null,
            connectedAt: new DateTimeImmutable($model->getAttribute('connected_at')->toDateTimeString()),
            disconnectedAt: $model->getAttribute('disconnected_at')
                ? new DateTimeImmutable($model->getAttribute('disconnected_at')->toDateTimeString())
                : null,
            metadata: $model->getAttribute('metadata'),
            createdAt: new DateTimeImmutable($model->getAttribute('created_at')->toDateTimeString()),
            updatedAt: new DateTimeImmutable($model->getAttribute('updated_at')->toDateTimeString()),
            deletedAt: $model->getAttribute('deleted_at')
                ? new DateTimeImmutable($model->getAttribute('deleted_at')->toDateTimeString())
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(SocialAccount $account): array
    {
        return [
            'id' => (string) $account->id,
            'organization_id' => (string) $account->organizationId,
            'connected_by' => (string) $account->connectedBy,
            'provider' => $account->provider->value,
            'provider_user_id' => $account->providerUserId,
            'username' => $account->username,
            'display_name' => $account->displayName,
            'profile_picture_url' => $account->profilePictureUrl,
            'access_token' => (string) $account->credentials->accessToken,
            'refresh_token' => $account->credentials->refreshToken
                ? (string) $account->credentials->refreshToken
                : null,
            'token_expires_at' => $account->credentials->expiresAt?->format('Y-m-d H:i:s'),
            'scopes' => $account->credentials->scopes,
            'status' => $account->status->value,
            'last_synced_at' => $account->lastSyncedAt?->format('Y-m-d H:i:s'),
            'connected_at' => $account->connectedAt->format('Y-m-d H:i:s'),
            'disconnected_at' => $account->disconnectedAt?->format('Y-m-d H:i:s'),
            'metadata' => $account->metadata,
            'deleted_at' => $account->deletedAt?->format('Y-m-d H:i:s'),
            'purge_at' => null,
        ];
    }
}
