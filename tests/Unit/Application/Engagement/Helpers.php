<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

/**
 * @param  array<string, mixed>  $overrides
 */
function createReconstitutedConnection(array $overrides = []): CrmConnection
{
    $orgId = isset($overrides['organizationId'])
        ? Uuid::fromString($overrides['organizationId'])
        : Uuid::generate();

    return CrmConnection::reconstitute(
        id: $overrides['id'] ?? Uuid::generate(),
        organizationId: $orgId,
        provider: CrmProvider::from($overrides['provider'] ?? 'hubspot'),
        accessToken: $overrides['accessToken'] ?? 'access-token-123',
        refreshToken: array_key_exists('refreshToken', $overrides) ? $overrides['refreshToken'] : 'refresh-token-456',
        tokenExpiresAt: $overrides['tokenExpiresAt'] ?? new DateTimeImmutable('+1 hour'),
        externalAccountId: $overrides['externalAccountId'] ?? 'ext-account-1',
        accountName: $overrides['accountName'] ?? 'My HubSpot',
        status: CrmConnectionStatus::from($overrides['status'] ?? 'connected'),
        settings: $overrides['settings'] ?? [],
        connectedBy: $overrides['connectedBy'] ?? Uuid::generate(),
        lastSyncAt: $overrides['lastSyncAt'] ?? null,
        createdAt: $overrides['createdAt'] ?? new DateTimeImmutable,
        updatedAt: $overrides['updatedAt'] ?? new DateTimeImmutable,
        disconnectedAt: $overrides['disconnectedAt'] ?? null,
    );
}
