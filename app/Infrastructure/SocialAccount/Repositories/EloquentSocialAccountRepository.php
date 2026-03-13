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
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class EloquentSocialAccountRepository implements SocialAccountRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 120; // 2 minutes

    public function __construct(
        private readonly SocialAccountModel $model,
        private readonly CacheRepository $cache,
    ) {}

    public function create(SocialAccount $account): void
    {
        $this->model->newQuery()->create($this->toArray($account));
        $this->invalidateCache($account->organizationId);
    }

    public function update(SocialAccount $account): void
    {
        $this->model->newQuery()
            ->where('id', (string) $account->id)
            ->update($this->toArray($account));
        $this->invalidateCache($account->organizationId);
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
        $cacheKey = $this->getCacheKey($organizationId);

        /** @var array<array<string, mixed>>|null $cached */
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return array_map(
                fn (array $data) => $this->toDomainFromCached($data),
                $cached
            );
        }

        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->whereNull('deleted_at')
            ->get();

        /** @var \Illuminate\Database\Eloquent\Collection<int, SocialAccountModel> $records */
        $accounts = $records->map(fn (SocialAccountModel $record) => $this->toDomain($record))->all();

        // Cache the raw data for serialization
        $cacheData = $records->map(fn (SocialAccountModel $r) => $r->toArray())->all();
        $this->cache->put($cacheKey, $cacheData, self::CACHE_TTL_SECONDS);

        return $accounts;
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
        // Get the account first to invalidate cache for its organization
        /** @var SocialAccountModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        $this->model->newQuery()
            ->where('id', (string) $id)
            ->delete();

        if ($record !== null) {
            $this->invalidateCache(Uuid::fromString($record->getAttribute('organization_id')));
        }
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

    private function getCacheKey(Uuid $organizationId): string
    {
        return "social_accounts:org:{$organizationId}";
    }

    private function invalidateCache(Uuid $organizationId): void
    {
        $this->cache->forget($this->getCacheKey($organizationId));
    }

    /**
     * Reconstitute domain object from cached array data.
     *
     * @param  array<string, mixed>  $data
     */
    private function toDomainFromCached(array $data): SocialAccount
    {
        $refreshToken = isset($data['refresh_token'])
            ? EncryptedToken::fromEncrypted($data['refresh_token'])
            : null;

        $credentials = OAuthCredentials::create(
            accessToken: EncryptedToken::fromEncrypted($data['access_token']),
            refreshToken: $refreshToken,
            expiresAt: isset($data['token_expires_at'])
                ? new DateTimeImmutable($data['token_expires_at'])
                : null,
            scopes: $data['scopes'] ?? [],
        );

        return SocialAccount::reconstitute(
            id: Uuid::fromString($data['id']),
            organizationId: Uuid::fromString($data['organization_id']),
            connectedBy: Uuid::fromString($data['connected_by']),
            provider: SocialProvider::from($data['provider']),
            providerUserId: $data['provider_user_id'],
            username: $data['username'],
            displayName: $data['display_name'],
            profilePictureUrl: $data['profile_picture_url'] ?? null,
            credentials: $credentials,
            status: ConnectionStatus::from($data['status']),
            lastSyncedAt: isset($data['last_synced_at'])
                ? new DateTimeImmutable($data['last_synced_at'])
                : null,
            connectedAt: new DateTimeImmutable($data['connected_at']),
            disconnectedAt: isset($data['disconnected_at'])
                ? new DateTimeImmutable($data['disconnected_at'])
                : null,
            metadata: $data['metadata'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            deletedAt: isset($data['deleted_at'])
                ? new DateTimeImmutable($data['deleted_at'])
                : null,
        );
    }
}
