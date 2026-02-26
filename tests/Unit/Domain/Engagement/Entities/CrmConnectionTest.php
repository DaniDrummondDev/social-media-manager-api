<?php

declare(strict_types=1);

use App\Domain\Engagement\Entities\CrmConnection;
use App\Domain\Engagement\Events\CrmConnected;
use App\Domain\Engagement\Events\CrmDisconnected;
use App\Domain\Engagement\Events\CrmTokenExpired;
use App\Domain\Engagement\Exceptions\InvalidCrmConnectionStatusTransitionException;
use App\Domain\Engagement\ValueObjects\CrmConnectionStatus;
use App\Domain\Engagement\ValueObjects\CrmProvider;
use App\Domain\Shared\ValueObjects\Uuid;

function createCrmConnection(array $overrides = []): CrmConnection
{
    return CrmConnection::create(
        organizationId: $overrides['organizationId'] ?? Uuid::generate(),
        provider: $overrides['provider'] ?? CrmProvider::HubSpot,
        accessToken: $overrides['accessToken'] ?? 'access-token-123',
        refreshToken: $overrides['refreshToken'] ?? 'refresh-token-456',
        tokenExpiresAt: $overrides['tokenExpiresAt'] ?? new DateTimeImmutable('+1 hour'),
        externalAccountId: $overrides['externalAccountId'] ?? 'ext-account-1',
        accountName: $overrides['accountName'] ?? 'My HubSpot',
        connectedBy: $overrides['connectedBy'] ?? Uuid::generate(),
        settings: $overrides['settings'] ?? [],
    );
}

it('creates with CrmConnected event and connected status', function () {
    $orgId = Uuid::generate();
    $userId = Uuid::generate();

    $connection = CrmConnection::create(
        organizationId: $orgId,
        provider: CrmProvider::HubSpot,
        accessToken: 'token-abc',
        refreshToken: 'refresh-xyz',
        tokenExpiresAt: new DateTimeImmutable('+30 minutes'),
        externalAccountId: 'hub-123',
        accountName: 'My HubSpot Account',
        connectedBy: $userId,
    );

    expect($connection->status)->toBe(CrmConnectionStatus::Connected)
        ->and($connection->provider)->toBe(CrmProvider::HubSpot)
        ->and($connection->accessToken)->toBe('token-abc')
        ->and($connection->refreshToken)->toBe('refresh-xyz')
        ->and($connection->externalAccountId)->toBe('hub-123')
        ->and($connection->accountName)->toBe('My HubSpot Account')
        ->and($connection->lastSyncAt)->toBeNull()
        ->and($connection->disconnectedAt)->toBeNull()
        ->and($connection->settings)->toBe([])
        ->and($connection->domainEvents)->toHaveCount(1)
        ->and($connection->domainEvents[0])->toBeInstanceOf(CrmConnected::class)
        ->and($connection->domainEvents[0]->provider)->toBe('hubspot')
        ->and($connection->domainEvents[0]->accountName)->toBe('My HubSpot Account');
});

it('disconnects with CrmDisconnected event', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $disconnected = $connection->disconnect($userId);

    expect($disconnected->status)->toBe(CrmConnectionStatus::Revoked)
        ->and($disconnected->disconnectedAt)->not->toBeNull()
        ->and($disconnected->domainEvents)->toHaveCount(2)
        ->and($disconnected->domainEvents[1])->toBeInstanceOf(CrmDisconnected::class)
        ->and($disconnected->domainEvents[1]->provider)->toBe('hubspot');
});

it('preserves immutability on disconnect', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $disconnected = $connection->disconnect($userId);

    expect($connection->status)->toBe(CrmConnectionStatus::Connected)
        ->and($disconnected->status)->toBe(CrmConnectionStatus::Revoked);
});

it('refreshes tokens and restores connected status', function () {
    $connection = createCrmConnection();
    $connection = $connection->releaseEvents();

    $refreshed = $connection->refreshTokens(
        accessToken: 'new-access-token',
        refreshToken: 'new-refresh-token',
        tokenExpiresAt: new DateTimeImmutable('+2 hours'),
    );

    expect($refreshed->accessToken)->toBe('new-access-token')
        ->and($refreshed->refreshToken)->toBe('new-refresh-token')
        ->and($refreshed->status)->toBe(CrmConnectionStatus::Connected)
        ->and($connection->accessToken)->toBe('access-token-123');
});

it('keeps existing refresh token when new one is null', function () {
    $connection = createCrmConnection(['refreshToken' => 'original-refresh']);

    $refreshed = $connection->refreshTokens(
        accessToken: 'new-access',
        refreshToken: null,
        tokenExpiresAt: null,
    );

    expect($refreshed->refreshToken)->toBe('original-refresh');
});

it('marks token expired with CrmTokenExpired event', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $expired = $connection->markTokenExpired($userId);

    expect($expired->status)->toBe(CrmConnectionStatus::TokenExpired)
        ->and($expired->domainEvents)->toHaveCount(2)
        ->and($expired->domainEvents[1])->toBeInstanceOf(CrmTokenExpired::class)
        ->and($expired->domainEvents[1]->connectionId)->toBe((string) $connection->id);
});

it('marks error status', function () {
    $connection = createCrmConnection();

    $errored = $connection->markError();

    expect($errored->status)->toBe(CrmConnectionStatus::Error)
        ->and($connection->status)->toBe(CrmConnectionStatus::Connected);
});

it('records sync updates lastSyncAt', function () {
    $connection = createCrmConnection();

    expect($connection->lastSyncAt)->toBeNull();

    $synced = $connection->recordSync();

    expect($synced->lastSyncAt)->not->toBeNull()
        ->and($connection->lastSyncAt)->toBeNull();
});

it('throws on invalid transition from revoked', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $revoked = $connection->disconnect($userId);

    $revoked->markError();
})->throws(InvalidCrmConnectionStatusTransitionException::class);

it('throws on invalid transition revoked to connected via disconnect again', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $revoked = $connection->disconnect($userId);

    $revoked->disconnect($userId);
})->throws(InvalidCrmConnectionStatusTransitionException::class);

it('allows transition from error to connected via refreshTokens', function () {
    $connection = createCrmConnection();
    $errored = $connection->markError();

    $refreshed = $errored->refreshTokens('new-token', null, null);

    expect($refreshed->status)->toBe(CrmConnectionStatus::Connected);
});

it('allows transition from token_expired to revoked', function () {
    $connection = createCrmConnection();
    $userId = (string) Uuid::generate();

    $expired = $connection->markTokenExpired($userId);
    $revoked = $expired->disconnect($userId);

    expect($revoked->status)->toBe(CrmConnectionStatus::Revoked);
});

it('reports isActive correctly', function () {
    $connection = createCrmConnection();

    expect($connection->isActive())->toBeTrue();

    $errored = $connection->markError();
    expect($errored->isActive())->toBeFalse();
});

it('reports canSync correctly', function () {
    $connection = createCrmConnection();

    expect($connection->canSync())->toBeTrue();

    $expired = $connection->markTokenExpired('user-1');
    expect($expired->canSync())->toBeFalse();
});

it('detects token expiry by timestamp', function () {
    $connection = createCrmConnection([
        'tokenExpiresAt' => new DateTimeImmutable('-1 hour'),
    ]);

    expect($connection->isTokenExpired())->toBeTrue();
});

it('reports not expired when no expiry timestamp', function () {
    $connection = createCrmConnection([
        'tokenExpiresAt' => null,
    ]);

    expect($connection->isTokenExpired())->toBeFalse();
});

it('releases events', function () {
    $connection = createCrmConnection();

    expect($connection->domainEvents)->toHaveCount(1);

    $released = $connection->releaseEvents();

    expect($released->domainEvents)->toHaveCount(0)
        ->and($connection->domainEvents)->toHaveCount(1);
});

it('reconstitutes without events', function () {
    $id = Uuid::generate();
    $orgId = Uuid::generate();
    $userId = Uuid::generate();
    $now = new DateTimeImmutable;

    $connection = CrmConnection::reconstitute(
        id: $id,
        organizationId: $orgId,
        provider: CrmProvider::Pipedrive,
        accessToken: 'token',
        refreshToken: 'refresh',
        tokenExpiresAt: $now,
        externalAccountId: 'pipe-1',
        accountName: 'Pipedrive Account',
        status: CrmConnectionStatus::Connected,
        settings: ['key' => 'value'],
        connectedBy: $userId,
        lastSyncAt: null,
        createdAt: $now,
        updatedAt: $now,
        disconnectedAt: null,
    );

    expect($connection->id)->toEqual($id)
        ->and($connection->provider)->toBe(CrmProvider::Pipedrive)
        ->and($connection->settings)->toBe(['key' => 'value'])
        ->and($connection->domainEvents)->toHaveCount(0);
});
