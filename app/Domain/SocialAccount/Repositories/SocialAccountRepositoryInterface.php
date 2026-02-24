<?php

declare(strict_types=1);

namespace App\Domain\SocialAccount\Repositories;

use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\SocialAccount\Entities\SocialAccount;
use App\Domain\SocialAccount\ValueObjects\SocialProvider;

interface SocialAccountRepositoryInterface
{
    public function create(SocialAccount $account): void;

    public function update(SocialAccount $account): void;

    public function findById(Uuid $id): ?SocialAccount;

    /** @return SocialAccount[] */
    public function findByOrganizationId(Uuid $organizationId): array;

    /** @return SocialAccount[] */
    public function findByOrganizationAndProvider(Uuid $organizationId, SocialProvider $provider): array;

    public function findByProviderAndProviderUserId(SocialProvider $provider, string $providerUserId): ?SocialAccount;

    public function delete(Uuid $id): void;

    /** @return SocialAccount[] */
    public function findExpiringTokens(int $minutesUntilExpiry): array;
}
