<?php

declare(strict_types=1);

namespace App\Infrastructure\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\Repositories\AdAccountRepositoryInterface;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountCredentials;
use App\Domain\PaidAdvertising\ValueObjects\AdAccountStatus;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Infrastructure\PaidAdvertising\Models\AdAccountModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

final class EloquentAdAccountRepository implements AdAccountRepositoryInterface
{
    public function __construct(
        private readonly AdAccountModel $model,
    ) {}

    public function create(AdAccount $account): void
    {
        $this->model->newQuery()->create($this->toArray($account));
    }

    public function update(AdAccount $account): void
    {
        $this->model->newQuery()
            ->where('id', (string) $account->id)
            ->update($this->toArray($account));
    }

    public function findById(Uuid $id): ?AdAccount
    {
        $record = $this->model->newQuery()->find((string) $id);

        return $record ? $this->toDomain($record) : null;
    }

    /**
     * @return array<AdAccount>
     */
    public function findByOrganizationId(Uuid $organizationId): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    /**
     * @return array<AdAccount>
     */
    public function findByOrganizationAndProvider(Uuid $organizationId, AdProvider $provider): array
    {
        $records = $this->model->newQuery()
            ->where('organization_id', (string) $organizationId)
            ->where('provider', $provider->value)
            ->orderByDesc('created_at')
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    public function findByProviderAndProviderAccountId(AdProvider $provider, string $providerAccountId): ?AdAccount
    {
        $record = $this->model->newQuery()
            ->where('provider', $provider->value)
            ->where('provider_account_id', $providerAccountId)
            ->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('id', (string) $id)->delete();
    }

    /**
     * @return array<AdAccount>
     */
    public function findExpiringTokens(int $minutesUntilExpiry): array
    {
        $threshold = (new DateTimeImmutable)->modify("+{$minutesUntilExpiry} minutes");

        $records = $this->model->newQuery()
            ->where('status', AdAccountStatus::Active->value)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $threshold->format('Y-m-d H:i:s'))
            ->get();

        return $records->map(fn (Model $r) => $this->toDomain($r))->all();
    }

    private function toDomain(Model $model): AdAccount
    {
        $tokenExpiresAt = $model->getAttribute('token_expires_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');
        $connectedAt = $model->getAttribute('connected_at');

        return AdAccount::reconstitute(
            id: Uuid::fromString($model->getAttribute('id')),
            organizationId: Uuid::fromString($model->getAttribute('organization_id')),
            connectedBy: Uuid::fromString($model->getAttribute('connected_by')),
            provider: AdProvider::from($model->getAttribute('provider')),
            providerAccountId: $model->getAttribute('provider_account_id'),
            providerAccountName: $model->getAttribute('provider_account_name'),
            credentials: AdAccountCredentials::create(
                encryptedAccessToken: $model->getAttribute('encrypted_access_token'),
                encryptedRefreshToken: $model->getAttribute('encrypted_refresh_token'),
                expiresAt: $tokenExpiresAt !== null
                    ? new DateTimeImmutable($tokenExpiresAt->format('Y-m-d H:i:s'))
                    : null,
                scopes: $model->getAttribute('scopes') ?? [],
            ),
            status: AdAccountStatus::from($model->getAttribute('status')),
            metadata: $model->getAttribute('metadata'),
            connectedAt: new DateTimeImmutable($connectedAt->format('Y-m-d H:i:s')),
            createdAt: new DateTimeImmutable($createdAt->format('Y-m-d H:i:s')),
            updatedAt: new DateTimeImmutable($updatedAt->format('Y-m-d H:i:s')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AdAccount $account): array
    {
        return [
            'id' => (string) $account->id,
            'organization_id' => (string) $account->organizationId,
            'connected_by' => (string) $account->connectedBy,
            'provider' => $account->provider->value,
            'provider_account_id' => $account->providerAccountId,
            'provider_account_name' => $account->providerAccountName,
            'encrypted_access_token' => $account->credentials->encryptedAccessToken,
            'encrypted_refresh_token' => $account->credentials->encryptedRefreshToken,
            'token_expires_at' => $account->credentials->expiresAt?->format('Y-m-d H:i:s'),
            'scopes' => $account->credentials->scopes,
            'status' => $account->status->value,
            'metadata' => $account->metadata,
            'connected_at' => $account->connectedAt->format('Y-m-d H:i:s'),
        ];
    }
}
