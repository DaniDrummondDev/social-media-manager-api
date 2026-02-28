<?php

declare(strict_types=1);

namespace App\Infrastructure\Engagement\Repositories;

use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\Repositories\CrmConnectionRepositoryInterface;
use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\Engagement\Models\CrmConnectionModel;
use App\Infrastructure\Engagement\Services\CrmTokenEncrypter;
use DateTimeImmutable;

final class EloquentCrmConnectionRepository implements CrmConnectionRepositoryInterface
{
    public function __construct(
        private readonly CrmConnectionModel $model,
        private readonly CrmTokenEncrypter $encrypter,
    ) {}

    public function create(CrmConnection $connection): void
    {
        $this->model->newQuery()->create($this->toArray($connection));
    }

    public function update(CrmConnection $connection): void
    {
        $this->model->newQuery()
            ->where('id', (string) $connection->id)
            ->update($this->toArray($connection));
    }

    public function findById(Uuid $id): ?CrmConnection
    {
        /** @var CrmConnectionModel|null $record */
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<CrmConnection>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmConnectionModel> $records */
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('status', '!=', CrmConnectionStatus::Revoked->value)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (CrmConnectionModel $r) => $this->toDomain($r))->all();
    }

    public function findByOrganizationAndProvider(Uuid $organizationId, CrmProvider $provider): ?CrmConnection
    {
        /** @var CrmConnectionModel|null $record */
        $record = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('provider', $provider->value)
            ->where('status', '!=', CrmConnectionStatus::Revoked->value)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<CrmConnection>
     */
    public function findExpiringTokens(int $minutesUntilExpiry): array
    {
        $threshold = now()->addMinutes($minutesUntilExpiry)->toDateTimeString();

        /** @var \Illuminate\Database\Eloquent\Collection<int, CrmConnectionModel> $records */
        $records = $this->model->newQuery()
            ->where('status', CrmConnectionStatus::Connected->value)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $threshold)
            ->get();

        return $records->map(fn (CrmConnectionModel $r) => $this->toDomain($r))->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(CrmConnection $connection): array
    {
        return [
            'id' => (string) $connection->id,
            'organization_id' => (string) $connection->organizationId,
            'provider' => $connection->provider->value,
            'access_token' => $connection->accessToken ? $this->encrypter->encrypt($connection->accessToken) : null,
            'refresh_token' => $connection->refreshToken ? $this->encrypter->encrypt($connection->refreshToken) : null,
            'token_expires_at' => $connection->tokenExpiresAt?->format('Y-m-d H:i:s'),
            'external_account_id' => $connection->externalAccountId,
            'account_name' => $connection->accountName,
            'status' => $connection->status->value,
            'settings' => $connection->settings,
            'connected_by' => (string) $connection->connectedBy,
            'last_sync_at' => $connection->lastSyncAt?->format('Y-m-d H:i:s'),
            'disconnected_at' => $connection->disconnectedAt?->format('Y-m-d H:i:s'),
        ];
    }

    private function toDomain(CrmConnectionModel $model): CrmConnection
    {
        $tokenExpiresAt = $model->getAttribute('token_expires_at');
        $lastSyncAt = $model->getAttribute('last_sync_at');
        $disconnectedAt = $model->getAttribute('disconnected_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        $accessToken = $model->getAttribute('access_token');
        $refreshToken = $model->getAttribute('refresh_token');

        return CrmConnection::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            provider: CrmProvider::from($model->getAttribute('provider')),
            accessToken: $accessToken ? $this->encrypter->decrypt($accessToken) : '',
            refreshToken: $refreshToken ? $this->encrypter->decrypt($refreshToken) : null,
            tokenExpiresAt: $tokenExpiresAt ? new DateTimeImmutable($tokenExpiresAt->format('Y-m-d H:i:s')) : null,
            externalAccountId: $model->getAttribute('external_account_id'),
            accountName: $model->getAttribute('account_name'),
            status: CrmConnectionStatus::from($model->getAttribute('status')),
            settings: $model->getAttribute('settings') ?? [],
            connectedBy: Uuid::fromString($model->getAttribute('connected_by')),
            lastSyncAt: $lastSyncAt ? new DateTimeImmutable($lastSyncAt->format('Y-m-d H:i:s')) : null,
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
            disconnectedAt: $disconnectedAt ? new DateTimeImmutable($disconnectedAt->format('Y-m-d H:i:s')) : null,
        );
    }
}
