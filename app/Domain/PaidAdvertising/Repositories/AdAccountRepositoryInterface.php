<?php

declare(strict_types=1);

namespace App\Domain\PaidAdvertising\Repositories;

use App\Domain\PaidAdvertising\Entities\AdAccount;
use App\Domain\PaidAdvertising\ValueObjects\AdProvider;
use App\Domain\Shared\ValueObjects\Uuid;

interface AdAccountRepositoryInterface
{
    public function create(AdAccount $account): void;

    public function update(AdAccount $account): void;

    public function findById(Uuid $id): ?AdAccount;

    /**
     * @return array<AdAccount>
     */
    public function findByOrganizationId(Uuid $organizationId): array;

    /**
     * @return array<AdAccount>
     */
    public function findByOrganizationAndProvider(Uuid $organizationId, AdProvider $provider): array;

    public function findByProviderAndProviderAccountId(AdProvider $provider, string $providerAccountId): ?AdAccount;

    public function delete(Uuid $id): void;

    /**
     * @return array<AdAccount>
     */
    public function findExpiringTokens(int $minutesUntilExpiry): array;
}
